// The client page keeps the hotel running while the website opens in a frame on top of it.
const pathOf = (url) => {
    try {
        return new URL(url, window.location.href).pathname;
    } catch {
        return "";
    }
};

const openShell = (shell) => {
    const clientFrame = document.getElementById("nitro");
    const websiteFrame = shell.querySelector("[data-website-frame]");
    const returnButton = shell.querySelector("[data-return-to-hotel]");
    const clientTitle = document.title;
    const clientAddress = window.location.href;

    const showClient = () => {
        websiteFrame.hidden = true;
        returnButton.hidden = true;
        clientFrame.inert = false;
        document.title = clientTitle;
        window.history.replaceState(window.history.state, "", clientAddress);
        clientFrame.focus();
    };

    shell
        .querySelector("[data-client-home]")
        ?.addEventListener("click", (event) => {
            event.preventDefault();
            websiteFrame.src = event.currentTarget.href;
            websiteFrame.hidden = false;
            returnButton.hidden = false;
            clientFrame.inert = true;
        });

    returnButton.addEventListener("click", (event) => {
        event.preventDefault();
        showClient();
    });

    window.atomClientShell = {
        clientPath: pathOf(shell.dataset.clientUrl),
        // Pages that must replace the whole shell, such as logout or another client.
        exitPaths: shell.dataset.exitUrls.split(" ").map(pathOf),
        showClient,
        follow: (page) => {
            if (websiteFrame.hidden) {
                return;
            }

            document.title = page.document.title;
            window.history.replaceState(
                window.history.state,
                "",
                page.location.href
            );
        },
    };
};

const joinShell = (host) => {
    const leavesShell = (element) => {
        const target =
            element.getAttribute("href") ?? element.getAttribute("action");

        return host.exitPaths.includes(pathOf(target));
    };

    const connect = () => {
        document
            .querySelectorAll("a[href], form[action]")
            .forEach((element) => {
                if (leavesShell(element)) {
                    element.target = "_top";
                }
            });

        host.follow(window);
    };

    window.addEventListener(
        "click",
        (event) => {
            const link = event.target.closest?.("a[href]");
            const plainClick =
                event.button === 0 &&
                !event.metaKey &&
                !event.ctrlKey &&
                !event.shiftKey &&
                !event.altKey;

            if (
                link &&
                plainClick &&
                link.origin === window.location.origin &&
                pathOf(link.href) === host.clientPath
            ) {
                event.preventDefault();
                host.showClient();
            }
        },
        true
    );

    document.addEventListener("turbolinks:load", connect);
    connect();
};

const parentShell = () => {
    try {
        return window.parent === window
            ? undefined
            : window.parent.atomClientShell;
    } catch {
        return undefined;
    }
};

const shell = document.querySelector("[data-client-shell]");
const host = parentShell();

if (shell && host) {
    // A second client page inside the website frame would log the player in twice.
    document.getElementById("nitro")?.remove();
    host.showClient();
} else if (shell) {
    openShell(shell);
} else if (host) {
    joinShell(host);
}
