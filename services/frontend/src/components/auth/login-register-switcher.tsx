"use client";

import { useState } from "react";

import { LoginForm } from "@/components/auth/login-form";
import { RegisterForm } from "@/components/auth/register-form";

type Mode = "login" | "register";

type AuthDict = {
  genericError: string;
  loginForm: {
    title: string;
    subtitle: string;
    email: string;
    password: string;
    submit: string;
    noAccount: string;
    createAccount: string;
  };
  registerForm: {
    title: string;
    subtitle: string;
    name: string;
    email: string;
    password: string;
    submit: string;
    haveAccount: string;
    login: string;
  };
};

export function LoginRegisterSwitcher({ dict }: { dict: AuthDict }) {
  const [mode, setMode] = useState<Mode>("login");

  return mode === "login" ? (
    <LoginForm
      dict={dict.loginForm}
      genericError={dict.genericError}
      onCreateAccount={() => setMode("register")}
    />
  ) : (
    <RegisterForm
      dict={dict.registerForm}
      genericError={dict.genericError}
      onLogin={() => setMode("login")}
    />
  );
}
