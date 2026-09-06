import { NextRequest, NextResponse } from "next/server";

import { postApi } from "@/api/api";

export async function POST(request: NextRequest) {
  const authorization = request.headers.get("authorization");
  if (!authorization) {
    return NextResponse.json({ message: "Unauthenticated." }, { status: 401 });
  }

  const body = await request.json();
  const { status, data } = await postApi("/api/v1/checkout", body, { Authorization: authorization });
  return NextResponse.json(data, { status });
}
