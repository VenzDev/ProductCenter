import { collectDefaultMetrics, Registry } from "prom-client";

// Guarded on globalThis so dev-mode hot reloads don't re-register the default
// metrics on a fresh Registry (prom-client throws on duplicate registration).
const globalForMetrics = globalThis as unknown as { metricsRegistry?: Registry };

export const metricsRegistry =
  globalForMetrics.metricsRegistry ??
  (() => {
    const registry = new Registry();
    collectDefaultMetrics({ register: registry });
    return registry;
  })();

if (process.env.NODE_ENV !== "production") {
  globalForMetrics.metricsRegistry = metricsRegistry;
}
