import { navData } from "../data/navData";
import { motion } from "framer-motion";
import React, { useState } from "react";

export default function Navbar() {
  const [hovered, setHovered] = useState<string | null>(null);
  return (
    <nav className="w-full flex justify-center py-2 bg-background border-b">
      <motion.ul initial={{ opacity: 0 }} animate={{ opacity: 1 }} className="flex gap-6">
        {navData.map((item) => (
          <li key={item.label} className="relative">
            <button
              className="text-sm font-medium bg-transparent border-none outline-none cursor-pointer px-1 py-1"
              onClick={() => {
                window.history.pushState({}, '', item.href);
                window.dispatchEvent(new PopStateEvent('popstate'));
              }}
              onMouseEnter={() => setHovered(item.label)}
              onMouseLeave={() => setHovered(null)}
              style={{ position: "relative" }}
            >
              {item.label}
              <motion.div
                layoutId="nav-underline"
                initial={false}
                animate={{
                  opacity: hovered === item.label ? 1 : 0,
                  y: hovered === item.label ? 0 : 8,
                }}
                transition={{ type: "spring", stiffness: 400, damping: 30 }}
                className="absolute left-0 right-0 -bottom-0.5 h-[2px] bg-primary rounded"
                style={{
                  opacity: hovered === item.label ? 1 : 0,
                  background: 'var(--color-primary, #6366f1)',
                }}
              />
            </button>
          </li>
        ))}
      </motion.ul>
    </nav>
  );
}
