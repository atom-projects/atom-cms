import { runsInstalled } from "./InstallApp.js";

// Keeps the game client the size of the visible screen on phones, also while the keyboard is open,
// and tells the client how much of the screen the notch and the home bar cover.
const fitClient = (viewportElement, frame, safeProbe) => {
    let clientTextFocused = false;

    const syncFrameViewport = (keyboardOpen) => {
        const style = window.getComputedStyle(safeProbe);
        const bounds = frame.getBoundingClientRect();
        const topGap = Math.max(0, bounds.top);
        const bottomGap = Math.max(0, window.innerHeight - bounds.bottom);
        const insets = {
            top: `${Math.max(0, parseFloat(style.paddingTop) - topGap)}px`,
            right: style.paddingRight || "0px",
            bottom: `${Math.max(0, parseFloat(style.paddingBottom) - bottomGap)}px`,
            left: style.paddingLeft || "0px",
        };

        try {
            const root = frame.contentDocument?.documentElement;

            if (root) {
                root.style.setProperty("--volt-safe-top", insets.top);
                root.style.setProperty("--volt-safe-right", insets.right);
                root.style.setProperty("--volt-safe-bottom", insets.bottom);
                root.style.setProperty("--volt-safe-left", insets.left);
            }
        } catch {
            // A cross-origin client gets the insets through postMessage below.
        }

        try {
            frame.contentWindow?.postMessage(
                {
                    type: "volt-safe-area",
                    ...insets,
                    layoutWidth: window.innerWidth,
                    layoutHeight: keyboardOpen
                        ? window.innerHeight
                        : bounds.height,
                    keyboardOpen,
                },
                "*",
            );
        } catch {
            // The frame is not ready yet; its load event sends the insets again.
        }
    };

    const fitVisualViewport = () => {
        const viewport = window.visualViewport;

        if (viewport && Math.abs(viewport.scale - 1) > 0.01) {
            return;
        }

        const keyboardOpen =
            clientTextFocused &&
            !!viewport &&
            window.innerHeight - viewport.height > 100;

        let height = viewport?.height ?? window.innerHeight;
        if (!keyboardOpen && runsInstalled()) {
            // A standalone webview can expose more space than its visual viewport reports.
            for (const edge of [window.innerHeight, window.screen.height]) {
                if (
                    edge > height &&
                    document.elementFromPoint(window.innerWidth / 2, edge - 1)
                ) {
                    height = edge;
                }
            }
        }

        viewportElement.style.height = `${height}px`;
        viewportElement.style.top = `${viewport?.offsetTop ?? 0}px`;
        viewportElement.style.bottom = "auto";
        syncFrameViewport(keyboardOpen);
    };

    window.addEventListener("message", (event) => {
        if (
            event.source === frame.contentWindow &&
            event.data?.type === "volt-viewport-request"
        ) {
            clientTextFocused = event.data.textFocused === true;
            fitVisualViewport();
        }
    });

    frame.addEventListener("load", fitVisualViewport);
    window.addEventListener("pageshow", fitVisualViewport);
    document.addEventListener("visibilitychange", fitVisualViewport);
    window.addEventListener("resize", fitVisualViewport);
    window.addEventListener("orientationchange", fitVisualViewport);
    window.visualViewport?.addEventListener("resize", fitVisualViewport);
    window.visualViewport?.addEventListener("scroll", fitVisualViewport);

    fitVisualViewport();
};

const viewportElement = document.querySelector("[data-client-viewport]");
const frame = document.getElementById("nitro");
const safeProbe = document.querySelector("[data-client-safe-area]");

if (viewportElement && frame && safeProbe) {
    fitClient(viewportElement, frame, safeProbe);
}
