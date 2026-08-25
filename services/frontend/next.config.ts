import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  output: "standalone",
  // Dev server rejects /_next/* asset requests whose Host isn't in this list. The e2e
  // suite (a separate container) reaches the app via the compose service name, not
  // localhost, so its origin needs to be allowed explicitly. Dev-only; ignored in prod builds.
  allowedDevOrigins: ["frontend-e2e"],
  images: {
    // Product images are served by LocalStack S3, reachable from the browser via the
    // published host port but not from inside the frontend container itself — see the
    // `unoptimized` prop on those <Image> usages, which skips the server-side fetch.
    remotePatterns: [
      {
        protocol: "http",
        hostname: "localhost",
        port: "4566",
        pathname: "/product-files/**",
      },
    ],
  },
};

export default nextConfig;
