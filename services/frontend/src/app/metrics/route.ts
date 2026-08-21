import { metricsRegistry } from "@/lib/metrics";

export async function GET() {
  const body = await metricsRegistry.metrics();

  return new Response(body, {
    headers: { "Content-Type": metricsRegistry.contentType },
  });
}
