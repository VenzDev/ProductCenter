"use client";

import Link from "next/link";
import { useParams, useRouter } from "next/navigation";
import { LogInIcon } from "lucide-react";

import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import { Button } from "@/components/ui/button";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuGroup,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { useCurrentUser } from "@/hooks/use-current-user";
import { clearToken } from "@/api/auth";
import { localizedHref } from "@/i18n/config";

function initials(name: string) {
  return name
    .split(" ")
    .map((part) => part[0])
    .filter(Boolean)
    .slice(0, 2)
    .join("")
    .toUpperCase();
}

type AuthStatusDict = {
  login: string;
  profile: string;
  logout: string;
};

export function AuthStatus({ dict }: { dict: AuthStatusDict }) {
  const router = useRouter();
  const { lang } = useParams<{ lang: string }>();
  const { user, loading } = useCurrentUser();

  if (loading) return null;

  if (!user) {
    return (
      <Button
        variant="outline"
        nativeButton={false}
        render={<Link href={localizedHref(lang, "/login")} />}
      >
        <LogInIcon data-icon="inline-start" />
        <span className="hidden sm:inline">{dict.login}</span>
      </Button>
    );
  }

  function handleLogout() {
    clearToken();
    router.push(localizedHref(lang, "/"));
    router.refresh();
  }

  return (
    <DropdownMenu>
      <DropdownMenuTrigger
        render={<Button variant="ghost" size="icon" className="cursor-pointer rounded-full" />}
      >
        <Avatar>
          <AvatarFallback>{initials(user.name)}</AvatarFallback>
        </Avatar>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" className="w-56">
        <DropdownMenuGroup>
          <DropdownMenuLabel>
            <div className="flex flex-col">
              <span className="font-medium">{user.name}</span>
              <span className="truncate text-xs font-normal text-muted-foreground">
                {user.email}
              </span>
            </div>
          </DropdownMenuLabel>
        </DropdownMenuGroup>
        <DropdownMenuSeparator />
        <DropdownMenuGroup>
          <DropdownMenuItem
            className="cursor-pointer text-sm"
            render={<Link href={localizedHref(lang, "/profile")} />}
          >
            {dict.profile}
          </DropdownMenuItem>
          <DropdownMenuItem className="cursor-pointer text-sm" onClick={handleLogout}>
            {dict.logout}
          </DropdownMenuItem>
        </DropdownMenuGroup>
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
