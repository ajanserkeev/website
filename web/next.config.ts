import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  // Minimal self-contained server for the Docker image (infra/docker/web.Dockerfile).
  output: "standalone",
};

export default nextConfig;
