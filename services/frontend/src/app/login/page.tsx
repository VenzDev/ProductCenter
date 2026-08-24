"use client";

import { useState } from "react";

import { LoginForm } from "@/components/auth/login-form";
import { RegisterForm } from "@/components/auth/register-form";

type Mode = "login" | "register";

export default function LoginPage() {
  const [mode, setMode] = useState<Mode>("login");

  return (
    <div className="flex flex-1 items-center justify-center px-4 py-16">
      {mode === "login" ? (
        <LoginForm onCreateAccount={() => setMode("register")} />
      ) : (
        <RegisterForm onLogin={() => setMode("login")} />
      )}
    </div>
  );
}
