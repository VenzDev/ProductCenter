"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { SearchIcon } from "lucide-react";

import { Button } from "@/components/ui/button";
import {
  Command,
  CommandDialog,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
} from "@/components/ui/command";
import { localizedHref } from "@/i18n/config";

type SearchDialogDict = {
  placeholder: string;
  dialogTitle: string;
  dialogDescription: string;
  noResults: string;
  suggestionsHeading: string;
  suggestions: string[];
  searchFor: string;
};

export function SearchDialog({
  dict,
  locale,
}: {
  dict: SearchDialogDict;
  locale: string;
}) {
  const [open, setOpen] = useState(false);
  const [value, setValue] = useState("");
  const router = useRouter();

  useEffect(() => {
    function onKeyDown(event: KeyboardEvent) {
      if (event.key === "k" && (event.metaKey || event.ctrlKey)) {
        event.preventDefault();
        setOpen((current) => !current);
      }
    }
    document.addEventListener("keydown", onKeyDown);
    return () => document.removeEventListener("keydown", onKeyDown);
  }, []);

  function runSearch(query: string) {
    const trimmed = query.trim();
    if (!trimmed) return;

    setOpen(false);
    setValue("");
    router.push(`${localizedHref(locale, "/search")}?q=${encodeURIComponent(trimmed)}`);
  }

  return (
    <>
      <Button
        variant="outline"
        onClick={() => setOpen(true)}
        className="justify-start gap-2 text-muted-foreground sm:w-56"
      >
        <SearchIcon data-icon="inline-start" />
        <span className="hidden sm:inline">{dict.placeholder}</span>
        <kbd className="ml-auto hidden rounded border px-1.5 text-xs text-muted-foreground sm:inline">
          ⌘K
        </kbd>
      </Button>
      <CommandDialog
        open={open}
        onOpenChange={setOpen}
        title={dict.dialogTitle}
        description={dict.dialogDescription}
      >
        <Command>
          <CommandInput
            placeholder={dict.placeholder}
            value={value}
            onValueChange={setValue}
          />
          <CommandList>
            <CommandEmpty>{dict.noResults}</CommandEmpty>
            {value.trim() && (
              <CommandGroup>
                <CommandItem onSelect={() => runSearch(value)}>
                  {dict.searchFor.replace("{query}", value.trim())}
                </CommandItem>
              </CommandGroup>
            )}
            <CommandGroup heading={dict.suggestionsHeading}>
              {dict.suggestions.map((item) => (
                <CommandItem key={item} onSelect={() => runSearch(item)}>
                  {item}
                </CommandItem>
              ))}
            </CommandGroup>
          </CommandList>
        </Command>
      </CommandDialog>
    </>
  );
}
