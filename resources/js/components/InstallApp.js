// Offers "add to Home Screen" with the same rules as the Nuxt frontend.
//
// Platforms:
// - android: the browser offers a one tap install.
// - ios-safari: Safari, installed through the Share menu.
// - ios-browser: Chrome, Edge or Firefox on iOS, which also have Add to Home Screen in their Share menu.
// - ios-in-app: an app's built-in browser, which cannot install; the player must open Safari.
const DISMISS_KEY = "atom-install-dismissed";

const DISMISS_DAYS = 14;

const GUIDE_POSITIONS = {
    phone: ["inset-x-3", "bottom-[calc(3.75rem+env(safe-area-inset-bottom))]"],
    wide: [
        "top-[calc(4rem+env(safe-area-inset-top))]",
        "right-4",
        "w-[min(380px,calc(100vw-2rem))]",
    ],
};

const ARROW_POSITIONS = {
    bottom: [
        "bottom-[calc(0.25rem+env(safe-area-inset-bottom))]",
        "left-1/2",
        "-translate-x-1/2",
    ],
    "bottom-left": [
        "bottom-[calc(0.25rem+env(safe-area-inset-bottom))]",
        "left-[28%]",
        "-translate-x-1/2",
    ],
    top: ["top-1", "right-24", "rotate-180"],
};

const state = {
    installEvent: null,
    installed: false,
    dismissed: true,
};

const prompts = new Map();

export function runsInstalled() {
    return (
        window.matchMedia("(display-mode: standalone)").matches ||
        window.matchMedia("(display-mode: fullscreen)").matches ||
        navigator.standalone === true
    );
}

function isIPad() {
    return (
        /iPad/.test(navigator.userAgent) ||
        (/Macintosh/.test(navigator.userAgent) && navigator.maxTouchPoints > 1)
    );
}

function detectPlatform() {
    const agent = navigator.userAgent;

    if (/Android/i.test(agent)) {
        return "android";
    }

    if (!/iPhone|iPod/.test(agent) && !isIPad()) {
        return null;
    }

    if (/CriOS|FxiOS|EdgiOS/.test(agent)) {
        return "ios-browser";
    }

    if (
        !/Safari\//.test(agent) ||
        /FBAN|FBAV|Instagram|Line\/|GSA\//.test(agent)
    ) {
        return "ios-in-app";
    }

    return "ios-safari";
}

function dismissedRecently() {
    try {
        const value = Number(localStorage.getItem(DISMISS_KEY));

        return value > Date.now() - DISMISS_DAYS * 86_400_000;
    } catch {
        return false;
    }
}

const device = {
    platform: null,
    iPad: false,
    // Safari 26 moved Share behind the ••• button.
    shareInMoreMenu: false,
};

function isVisible() {
    if (!device.platform || state.dismissed || state.installed) {
        return false;
    }

    return device.platform !== "android" || state.installEvent !== null;
}

function renderAll() {
    prompts.forEach((render) => render());
}

function dismiss() {
    state.dismissed = true;

    try {
        localStorage.setItem(DISMISS_KEY, String(Date.now()));
    } catch {
        // Without storage the banner returns on the next visit.
    }

    renderAll();
}

async function install() {
    const event = state.installEvent;

    if (!event) {
        return;
    }

    state.installEvent = null;
    renderAll();

    await event.prompt();

    if ((await event.userChoice).outcome === "dismissed") {
        dismiss();
    }
}

function shareStep() {
    if (device.platform === "ios-browser") {
        return "browser";
    }

    if (device.shareInMoreMenu && !device.iPad) {
        return "menu";
    }

    return device.iPad ? "top" : "bottom";
}

function arrowPosition() {
    if (device.platform !== "ios-safari") {
        return null;
    }

    if (device.iPad) {
        return "top";
    }

    return device.shareInMoreMenu ? "bottom-left" : "bottom";
}

function showOnly(elements, attribute, value) {
    elements.forEach((element) => {
        element.hidden = element.getAttribute(attribute) !== value;
    });
}

function bindPrompt(root) {
    const banner = root.querySelector("[data-install-banner]");
    const guide = root.querySelector("[data-install-guide]");
    const section = guide.querySelector("section");
    const arrow = guide.querySelector("[data-install-arrow]");
    const closeButton = guide.querySelector("[data-install-close]");
    const copyLabels = guide.querySelectorAll("[data-install-copy-label]");
    let guideOpen = false;

    const render = () => {
        const visible = isVisible();

        root.hidden = !visible;
        banner.hidden = !visible || guideOpen;
        guide.hidden = !visible || !guideOpen;
    };

    const setGuideOpen = (open) => {
        guideOpen = open;
        render();
    };

    const iPhone = device.platform !== "android" && !device.iPad;
    section.classList.add(...GUIDE_POSITIONS[iPhone ? "phone" : "wide"]);

    const arrowAt = arrowPosition();
    arrow.hidden = arrowAt === null;
    if (arrowAt) {
        arrow.classList.add(...ARROW_POSITIONS[arrowAt]);
    }

    showOnly(
        root.querySelectorAll("[data-install-label]"),
        "data-install-label",
        device.platform === "android" ? "install" : "guide",
    );
    showOnly(
        guide.querySelectorAll("[data-install-share]"),
        "data-install-share",
        shareStep(),
    );
    guide.querySelector("[data-install-in-app]").hidden =
        device.platform !== "ios-in-app";
    guide.querySelector("[data-install-steps]").hidden =
        device.platform === "ios-in-app";
    guide.querySelector("[data-install-browser-hint]").hidden =
        device.platform !== "ios-browser";

    root.querySelector("[data-install-dismiss]").addEventListener(
        "click",
        dismiss,
    );

    root.querySelector("[data-install-action]").addEventListener(
        "click",
        () => {
            if (device.platform === "android") {
                install();

                return;
            }

            setGuideOpen(true);
            closeButton.focus();
        },
    );

    closeButton.addEventListener("click", () => setGuideOpen(false));

    guide.addEventListener("click", (event) => {
        if (event.target === guide) {
            setGuideOpen(false);
        }
    });

    guide.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            setGuideOpen(false);
        }
    });

    guide
        .querySelector("[data-install-finish]")
        .addEventListener("click", () => {
            guideOpen = false;
            dismiss();
        });

    guide
        .querySelector("[data-install-copy]")
        .addEventListener("click", async () => {
            let copied = false;

            try {
                await navigator.clipboard.writeText(window.location.href);
                copied = true;
            } catch {
                copied = false;
            }

            showOnly(
                copyLabels,
                "data-install-copy-label",
                copied ? "copied" : "copy",
            );
        });

    return render;
}

function connectPrompts() {
    prompts.forEach((_, root) => {
        if (!root.isConnected) {
            prompts.delete(root);
        }
    });

    document.querySelectorAll("[data-install-app]").forEach((root) => {
        if (!prompts.has(root)) {
            prompts.set(root, bindPrompt(root));
        }
    });

    renderAll();
}

function registerServiceWorker() {
    if (import.meta.env.DEV || !("serviceWorker" in navigator)) {
        return;
    }

    const register = () =>
        navigator.serviceWorker.register("/sw.js").catch(() => {
            // The site works the same without the worker; only installing needs it.
        });

    if (document.readyState === "complete") {
        register();
    } else {
        window.addEventListener("load", register, { once: true });
    }
}

if (window.top === window.self) {
    window.addEventListener("beforeinstallprompt", (event) => {
        event.preventDefault();
        state.installEvent = event;
        renderAll();
    });

    window.addEventListener("appinstalled", () => {
        state.installed = true;
        state.installEvent = null;
        renderAll();
    });

    registerServiceWorker();

    if (!runsInstalled()) {
        device.platform = detectPlatform();
        device.iPad = isIPad();
        device.shareInMoreMenu =
            Number(navigator.userAgent.match(/Version\/(\d+)/)?.[1] || 0) >= 26;
        state.dismissed = dismissedRecently();

        document.addEventListener("turbolinks:load", connectPrompts);
        connectPrompts();
    }
}
