import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  output: "standalone",
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
