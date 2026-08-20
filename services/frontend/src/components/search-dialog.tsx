"use client";

import { useEffect, useState } from "react";
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

const SUGGESTIONS = [
  "Electronics",
  "Clothing",
  "Home & Garden",
  "New Arrivals",
  "Best Sellers",
];

export function SearchDialog() {
  const [open, setOpen] = useState(false);

  useEffect(() => {
    function onKeyDown(event: KeyboardEvent) {
      if (event.key === "k" && (event.metaKey || event.ctrlKey)) {
        event.preventDefault();
        setOpen((value) => !value);
      }
    }
    document.addEventListener("keydown", onKeyDown);
    return () => document.removeEventListener("keydown", onKeyDown);
  }, []);

  return (
    <>
      <Button
        variant="outline"
        onClick={() => setOpen(true)}
        className="justify-start gap-2 text-muted-foreground sm:w-56"
      >
        <SearchIcon data-icon="inline-start" />
        <span className="hidden sm:inline">Search products...</span>
        <kbd className="ml-auto hidden rounded border px-1.5 text-xs text-muted-foreground sm:inline">
          ⌘K
        </kbd>
      </Button>
      <CommandDialog
        open={open}
        onOpenChange={setOpen}
        title="Search products"
        description="Search is a mockup for now — no results are wired up yet."
      >
        <Command>
          <CommandInput placeholder="Search products..." />
          <CommandList>
            <CommandEmpty>No results found.</CommandEmpty>
            <CommandGroup heading="Suggestions">
              {SUGGESTIONS.map((item) => (
                <CommandItem key={item} onSelect={() => setOpen(false)}>
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
