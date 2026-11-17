// The game client posts "logout-requested" on the same channel when it runs outside the client page.
const channel =
    typeof BroadcastChannel === "undefined"
        ? null
        : new BroadcastChannel("hotel-session");

const onClientPage = () => document.body?.id === "nitro-client";

function leave() {
    if (onClientPage()) {
        // Browsers only let a script close a tab that a script opened or that has one history entry.
        window.close();

        if (!window.closed) {
            window.location.replace("/");
        }

        return;
    }

    window.location.reload();
}

async function logOut() {
    const response = await fetch("/logout", {
        method: "POST",
        credentials: "same-origin",
        headers: {
            Accept: "application/json",
            "X-Requested-With": "XMLHttpRequest",
            "X-CSRF-TOKEN":
                document.querySelector('meta[name="csrf-token"]')?.content ??
                "",
        },
    });

    if (response.ok) {
        channel?.postMessage({ type: "logged-out" });
    }

    if (response.ok || response.status === 401) {
        leave();
    }
}

channel?.addEventListener("message", (event) => {
    if (event.data?.type === "logged-out") {
        leave();
    } else if (event.data?.type === "logout-requested") {
        logOut();
    }
});

if (onClientPage()) {
    window.FlashExternalInterface ??= {};
    window.FlashExternalInterface.logout = logOut;
}
