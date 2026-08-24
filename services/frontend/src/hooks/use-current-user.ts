"use client";

import { useEffect, useState } from "react";

import { AUTH_CHANGE_EVENT, getCurrentUser, type User } from "@/api/auth";

export function useCurrentUser() {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let active = true;

    async function load() {
      setLoading(true);
      const current = await getCurrentUser();
      if (active) {
        setUser(current);
        setLoading(false);
      }
    }

    load();
    window.addEventListener(AUTH_CHANGE_EVENT, load);
    return () => {
      active = false;
      window.removeEventListener(AUTH_CHANGE_EVENT, load);
    };
  }, []);

  return { user, loading };
}
