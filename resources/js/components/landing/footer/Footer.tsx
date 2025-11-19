import { useState, useEffect } from "react";
import { Facebook, Instagram, Youtube, MessageCircle } from "lucide-react";
import { navData } from "../data/navData";
import { motion } from "framer-motion";

export default function Footer() {
  const [isDark, setIsDark] = useState(false);
  const [hovered, setHovered] = useState<string | null>(null);

  useEffect(() => {
    const updateTheme = () => {
      setIsDark(document.documentElement.classList.contains("dark"));
    };
    updateTheme();
    const observer = new MutationObserver(updateTheme);
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ["class"] });
    return () => observer.disconnect();
  }, []);

  const getLogo = () => {
    return isDark ? "/landing/logo_negro.png" : "/landing/logo_negro.png";
  };

  return (
    <footer className="w-full border-t bg-background text-foreground transition-colors duration-300">
      <div className="max-w-7xl mx-auto px-4 py-10 flex flex-col gap-10 md:flex-row md:items-start md:justify-between">
        {/* Logo y redes */}
        <div className="flex flex-col items-center md:items-start md:w-1/3 mb-6 md:mb-0">
          <img
            src={getLogo()}
            alt="Layla IA Logo"
            className="h-10 w-auto object-contain transition-transform duration-300 hover:scale-105 mb-4"
            style={{ filter: "drop-shadow(0 2px 8px rgba(0,0,0,0.12))" }}
          />
          <div className="flex items-center space-x-6 md:space-x-4 mt-2 md:mt-6">
            <a href="#" aria-label="Facebook">
              <Facebook className="h-6 w-6 cursor-pointer transition-colors hover:text-primary" />
            </a>
            <a href="#" aria-label="Instagram">
              <Instagram className="h-6 w-6 cursor-pointer transition-colors hover:text-primary" />
            </a>
            <a href="#" aria-label="Youtube">
              <Youtube className="h-6 w-6 cursor-pointer transition-colors hover:text-primary" />
            </a>
            <a href="#" aria-label="Contacto">
              <MessageCircle className="h-6 w-6 cursor-pointer transition-colors hover:text-primary" />
            </a>
          </div>
        </div>
        {/* Navegación SPA con animación */}
        <div className="flex flex-col md:flex-row md:gap-16 w-full md:w-2/3 justify-center md:justify-end">
          <div className="mb-8 md:mb-0">
            <h4 className="font-semibold text-base mb-3 text-center md:text-left">Navegación</h4>
            <ul className="flex flex-col gap-2 md:gap-3">
              {navData.map((link) => (
                <li key={link.label} className="relative">
                  <button
                    className="text-sm font-medium text-foreground px-2 py-1 rounded-lg transition-colors bg-transparent border-none outline-none cursor-pointer w-full text-left"
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
                </li>
              ))}
            </ul>
          </div>
        </div>
      </div>
      <div className="border-t py-4 bg-background">
        <div className="max-w-7xl mx-auto px-4 text-center text-xs text-muted-foreground flex flex-col md:flex-row md:justify-between md:items-center">
          <p>
            © {new Date().getFullYear()} Layla IA. Todos los derechos reservados.
          </p>
          <div className="flex gap-4 justify-center mt-2 md:mt-0">
            <a href="#" className="hover:underline">Términos</a>
            <a href="#" className="hover:underline">Privacidad</a>
            <a href="#" className="hover:underline">Seguridad</a>
          </div>
        </div>
      </div>
    </footer>
  );
}