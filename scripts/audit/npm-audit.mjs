import { spawnSync } from "node:child_process";

// Advisories with no patched version. Remove an entry once a fixed release exists.
const ignored = new Map([
    ["GHSA-fc5q-39xc-vjpf", "picomatch has no patched version (affects <= 4.0.7)."],
]);

const result = spawnSync("npm", ["audit", "--json", "--audit-level=moderate"], { encoding: "utf8", maxBuffer: 64 * 1024 * 1024 });
const report = JSON.parse(result.stdout);

if (report.error) {
    console.error(report.error.summary ?? "npm audit failed");
    process.exit(1);
}

const advisories = new Map();

for (const vulnerability of Object.values(report.vulnerabilities ?? {})) {
    for (const via of vulnerability.via) {
        if (typeof via === "object" && via.severity !== "low") {
            advisories.set(via.url, via);
        }
    }
}

const blocking = [...advisories.values()].filter((advisory) => ![...ignored.keys()].some((id) => advisory.url.endsWith(id)));

for (const [id, reason] of ignored) {
    console.log(`Ignored ${id}: ${reason}`);
}

for (const advisory of blocking) {
    console.error(`${advisory.severity} ${advisory.name}: ${advisory.title} (${advisory.url})`);
}

process.exit(blocking.length > 0 ? 1 : 0);
