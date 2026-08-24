import { NextRequest, NextResponse } from "next/server";

import { postApi } from "@/api/api";

export async function POST(request: NextRequest) {
  const body = await request.json();
  const { status, data } = await postApi("/api/v1/login", body);
  return NextResponse.json(data, { status });
}
