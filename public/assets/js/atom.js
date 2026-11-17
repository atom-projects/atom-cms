// Disconnected from client
function disconnected() {
    document.querySelector("#disconnected").style =
        "display: block !important;";
}

const frame = document.getElementById("nitro");
window.FlashExternalInterface ??= {};
window.FlashExternalInterface.disconnect = () => {
    disconnected();
};

if (frame) {
    const clientSource = new URL(frame.src);

    window.addEventListener("message", (event) => {
        if (
            event.source !== frame.contentWindow ||
            event.origin !== clientSource.origin ||
            typeof event.data !== "string"
        ) return;

        const prefix = [
            "Nitro_LegacyExternalInterface",
            "Volt_LegacyExternalInterface",
        ].find((value) => event.data.startsWith(value));
        if (!prefix) return;

        let message;
        try {
            message = JSON.parse(event.data.slice(prefix.length));
        } catch {
            return;
        }

        if (message?.method === "disconnect") {
            window.FlashExternalInterface.disconnect();
        } else if (message?.method === "logout") {
            window.FlashExternalInterface.logout?.();
        }
    });

    const clientBase =
        clientSource.origin + clientSource.pathname.replace(/[^/]*$/, "");

    frame.addEventListener("load", () => {
        let destination;

        try {
            destination = frame.contentWindow.location.href;
        } catch (error) {
            // Nitro is hosted on another origin, so we cannot tell where it went.
            return;
        }

        if (
            !destination.startsWith("http") ||
            destination.startsWith(clientBase)
        ) {
            return;
        }

        window.location.replace(destination);
    });
}
