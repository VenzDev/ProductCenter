"use client";

import { useState, type FormEvent } from "react";
import { useParams, useRouter } from "next/navigation";
import { CircleAlertIcon } from "lucide-react";

import { Alert, AlertTitle } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Field, FieldGroup, FieldLabel } from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import { register } from "@/api/auth";
import { localizedHref } from "@/i18n/config";

type RegisterFormDict = {
  title: string;
  subtitle: string;
  name: string;
  email: string;
  password: string;
  submit: string;
  haveAccount: string;
  login: string;
};

export function RegisterForm({
  dict,
  genericError,
  onLogin,
}: {
  dict: RegisterFormDict;
  genericError: string;
  onLogin: () => void;
}) {
  const router = useRouter();
  const { lang } = useParams<{ lang: string }>();
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setError(null);
    setSubmitting(true);

    try {
      await register(name, email, password);
      router.push(localizedHref(lang, "/"));
      router.refresh();
    } catch (err) {
      setError(err instanceof Error ? err.message : genericError);
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <Card className="w-full max-w-sm">
      <CardHeader>
        <CardTitle>{dict.title}</CardTitle>
        <CardDescription>{dict.subtitle}</CardDescription>
      </CardHeader>
      <CardContent>
        <form onSubmit={handleSubmit}>
          <FieldGroup>
            {error && (
              <Alert variant="destructive">
                <CircleAlertIcon data-icon="inline-start" />
                <AlertTitle>{error}</AlertTitle>
              </Alert>
            )}
            <Field>
              <FieldLabel htmlFor="name">{dict.name}</FieldLabel>
              <Input
                id="name"
                value={name}
                onChange={(event) => setName(event.target.value)}
                required
              />
            </Field>
            <Field>
              <FieldLabel htmlFor="email">{dict.email}</FieldLabel>
              <Input
                id="email"
                type="email"
                value={email}
                onChange={(event) => setEmail(event.target.value)}
                required
              />
            </Field>
            <Field>
              <FieldLabel htmlFor="password">{dict.password}</FieldLabel>
              <Input
                id="password"
                type="password"
                value={password}
                onChange={(event) => setPassword(event.target.value)}
                minLength={8}
                required
              />
            </Field>
            <Button type="submit" disabled={submitting} className="cursor-pointer">
              {dict.submit}
            </Button>
          </FieldGroup>
        </form>
        <p className="mt-4 text-center text-sm text-muted-foreground">
          {dict.haveAccount}{" "}
          <button
            type="button"
            onClick={onLogin}
            className="cursor-pointer font-medium text-foreground underline underline-offset-4"
          >
            {dict.login}
          </button>
        </p>
      </CardContent>
    </Card>
  );
}
