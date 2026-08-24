import { NextRequest, NextResponse } from "next/server";

import { proxyGet } from "@/api/api";

export async function GET(request: NextRequest) {
  const authorization = request.headers.get("authorization");
  if (!authorization) {
    return NextResponse.json({ message: "Unauthenticated." }, { status: 401 });
  }

  const { status, data } = await proxyGet("/api/v1/me", { Authorization: authorization });
  return NextResponse.json(data, { status });
}
