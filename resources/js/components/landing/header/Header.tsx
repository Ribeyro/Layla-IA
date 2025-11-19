import { useState, useEffect } from "react";
import { Menu, X } from "lucide-react";
import { navData } from "../data/navData";
import { motion } from "framer-motion";

export default function Header() {
  const [isDark, setIsDark] = useState(false);
  const [scrolled, setScrolled] = useState(false);
  const [mobileMenu, setMobileMenu] = useState(false);
  const [hovered, setHovered] = useState<string | null>(null);

  useEffect(() => {
    setIsDark(document.documentElement.classList.contains("dark"));
    const handleScroll = () => setScrolled(window.scrollY > 10);
    window.addEventListener("scroll", handleScroll);
    return () => window.removeEventListener("scroll", handleScroll);
  }, []);

  const getLogo = () => {
    if (window.innerWidth < 768) {
      return "/landing/logo_mobile.png";
    }
    return isDark ? "/landing/logo_negro.png" : "/landing/logo_negro.png";
  };

  return (
    <header
      className={`fixed top-0 left-0 w-full z-50 transition-all duration-300 ${
        scrolled ? "bg-background/80 shadow-lg backdrop-blur-md" : "bg-background"
      }`}
    >
      <div className="max-w-7xl mx-auto flex items-center justify-between px-4 py-3">
        <a href="/" className="flex items-center gap-2">
          <img src={getLogo()} alt="Layla IA Logo" className="h-10 w-auto object-contain" />
        </a>
        <nav className="hidden md:flex flex-1 justify-center gap-8">
          {navData.map((link) => (
            <div key={link.label} className="relative">
              <button
                className="text-sm font-medium text-foreground transition-colors px-2 py-1 rounded-lg bg-transparent border-none outline-none cursor-pointer"
                onClick={() => {
                  window.history.pushState({}, '', link.href);
                  window.dispatchEvent(new PopStateEvent('popstate'));
                }}
                onMouseEnter={() => setHovered(link.label)}
                onMouseLeave={() => setHovered(null)}
                style={{ position: "relative" }}
              >
                {link.label}
                <motion.div
                  initial={{ scaleX: 0, opacity: 0 }}
                  animate={hovered === link.label ? { scaleX: 1, opacity: 1 } : { scaleX: 0, opacity: 0 }}
                  transition={{ type: "spring", stiffness: 400, damping: 30 }}
                  className="absolute left-0 right-0 -bottom-0.5 h-[2px] bg-primary rounded"
                  style={{
                    background: 'var(--color-primary, #6366f1)',
                    transformOrigin: 'left',
                  }}
                />
              </button>
            </div>
          ))}
        </nav>
        <div className="hidden md:flex gap-3 ml-4">
          <a href="#" className="px-5 py-2 rounded-lg bg-primary text-white font-semibold shadow hover:bg-primary/80 transition">Descargar App</a>
        </div>
        <button
          type="button"
          className="md:hidden p-2 ml-2 rounded-full hover:bg-muted"
          aria-label="Abrir menú"
          onClick={() => setMobileMenu(!mobileMenu)}
        >
          {mobileMenu ? <X size={24} /> : <Menu size={24} />}
        </button>
      </div>
      {mobileMenu && (
        <nav className="md:hidden bg-background border-t border-border px-4 py-4 flex flex-col gap-4">
          {navData.map((link) => (
            <button
              key={link.label}
              className="text-base font-medium text-foreground hover:text-primary transition-colors px-2 py-2 rounded-lg hover:bg-muted"
              onClick={() => {
                window.history.pushState({}, '', link.href);
                window.dispatchEvent(new PopStateEvent('popstate'));
                setMobileMenu(false);
              }}
            >
              {link.label}
            </button>
          ))}
        </nav>
      )}
    </header>
  );
}
