import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  // Minimal self-contained server for the Docker image (infra/docker/web.Dockerfile).
  output: "standalone",
  images: {
    // Photos come from Laravel in prepared WebP sizes; see src/lib/image-loader.ts.
    loader: "custom",
    loaderFile: "./src/lib/image-loader.ts",
  },
};

export default nextConfig;
