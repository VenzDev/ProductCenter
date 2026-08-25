import { LoginRegisterSwitcher } from "@/components/auth/login-register-switcher";
import { getDictionary } from "@/app/[lang]/dictionaries";

export default async function LoginPage() {
  const dict = await getDictionary();

  return (
    <div className="flex flex-1 items-center justify-center px-4 py-16">
      <LoginRegisterSwitcher dict={dict.auth} />
    </div>
  );
}
