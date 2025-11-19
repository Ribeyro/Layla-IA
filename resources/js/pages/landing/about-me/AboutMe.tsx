import React, { useState, useEffect } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { aboutUsData } from "@/components/landing/data/aboutUsData";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Github, Twitter, Linkedin, X } from "lucide-react";

export default function AboutMe() {
  const [selected, setSelected] = useState<number | null>(null);
  const [scrolled, setScrolled] = useState(false);

  useEffect(() => {
    const handleScroll = () => setScrolled(window.scrollY > 40);
    window.addEventListener("scroll", handleScroll);
    return () => window.removeEventListener("scroll", handleScroll);
  }, []);

  const getGridCols = () => {
    const count = aboutUsData.team.members.length;
    if (count === 1) return "grid-cols-1 justify-center";
    if (count === 2) return "grid-cols-1 md:grid-cols-2 justify-center";
    return "grid-cols-1 md:grid-cols-3";
  };

  return (
    <div className="w-full py-16">
      <motion.section
        className={`container mx-auto px-4 mb-20 transition-all duration-300 ${
          scrolled ? "rounded-2xl mt-8" : ""
        }`}
        initial={{ opacity: 0, y: 40 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.7, ease: "easeOut" }}
      >
        <div className="flex flex-col md:flex-row items-center md:items-start gap-5">
          <div className="flex-1 flex flex-col items-center">
            <h1 className="text-4xl font-black text-primary mb-6 select-none drop-shadow-lg text-center">
              {aboutUsData.about.title}
            </h1>
            <div className="w-full max-w-3xl mb-8">
              <p className="text-lg md:text-lg text-foreground text-center leading-relaxed font-medium bg-transparent px-0 py-0">
                {aboutUsData.about.description}
              </p>
            </div>
          </div>
          <div className="flex-1 flex flex-col items-end w-full">
            <ul className="flex flex-wrap md:flex-col gap-4 justify-end text-center">
              {aboutUsData.about.highlights.map((item, idx) => (
                <li key={idx} className="bg-primary/10 px-5 py-2 rounded-xl text-base text-primary font-semibold shadow-sm text-center">
                  {item}
                </li>
              ))}
            </ul>
          </div>
        </div>
      </motion.section>

      <motion.section
        className="container mx-auto px-4"
        initial={{ opacity: 0, y: 40 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.7, ease: "easeOut", delay: 0.2 }}
      >
        <h2 className="text-3xl font-bold text-center mb-4 select-none text-primary">{aboutUsData.team.title}</h2>
        <p className="text-lg text-muted-foreground text-center mb-10 max-w-2xl mx-auto">
          {aboutUsData.team.description}
        </p>
        <div className={`grid ${getGridCols()} gap-8 justify-center`}>
          {aboutUsData.team.members.map((member, idx) => (
            <motion.div
              key={member.name}
              className="flex justify-center items-center animate-on-scroll"
              initial={{ opacity: 0, scale: 0.9, y: 40 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              transition={{ duration: 0.7, ease: "easeOut", delay: idx * 0.15 }}
            >
              <Card
                className="bg-white border-0 shadow-lg rounded-2xl flex flex-col items-center py-8 px-6 h-full w-full cursor-pointer hover:scale-105 transition-transform"
                onClick={() => setSelected(idx)}
              >
                <CardHeader>
                  <div className="flex flex-col items-center">
                    <img src={member.avatar} alt={member.name} className="w-24 h-24 rounded-full mb-4 shadow-lg object-cover bg-muted" />
                    <CardTitle className="text-center text-xl font-bold text-foreground mb-1 select-none">
                      {member.name}
                    </CardTitle>
                    <span className="text-base text-primary font-semibold mb-2">{member.role}</span>
                  </div>
                </CardHeader>
                <CardContent>
                  <p className="text-muted-foreground text-center text-base leading-relaxed mb-4">{member.bio}</p>
                  <div className="flex justify-center gap-4">
                    <a href={member.social.github} target="_blank" rel="noopener noreferrer" className="bg-muted p-2 rounded-lg hover:bg-primary hover:text-white transition-colors">
                      <Github className="w-5 h-5" />
                    </a>
                    <a href={member.social.twitter} target="_blank" rel="noopener noreferrer" className="bg-muted p-2 rounded-lg hover:bg-primary hover:text-white transition-colors">
                      <Twitter className="w-5 h-5" />
                    </a>
                    <a href={member.social.linkedin} target="_blank" rel="noopener noreferrer" className="bg-muted p-2 rounded-lg hover:bg-primary hover:text-white transition-colors">
                      <Linkedin className="w-5 h-5" />
                    </a>
                  </div>
                </CardContent>
              </Card>
            </motion.div>
          ))}
        </div>
      </motion.section>

      <AnimatePresence>
        {selected !== null && (
          <motion.div
            className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
          >
            <motion.div
              className="bg-white rounded-2xl shadow-2xl p-8 max-w-md w-full relative"
              initial={{ scale: 0.9, y: 40 }}
              animate={{ scale: 1, y: 0 }}
              exit={{ scale: 0.9, y: 40 }}
              transition={{ duration: 0.3 }}
            >
              <button
                className="absolute top-4 right-4 text-muted-foreground hover:text-primary transition-colors"
                onClick={() => setSelected(null)}
                aria-label="Cerrar"
              >
                <X className="w-6 h-6" />
              </button>
              {(() => {
                const member = aboutUsData.team.members[selected];
                return (
                  <>
                    <div className="flex flex-col items-center mb-4">
                      <img src={member.avatar} alt={member.name} className="w-20 h-20 rounded-full mb-2 shadow-lg object-cover bg-muted" />
                      <h3 className="text-xl font-bold text-foreground mb-1">{member.name}</h3>
                      <span className="text-base text-primary font-semibold mb-2">{member.role}</span>
                    </div>
                    <p className="text-muted-foreground text-center text-base leading-relaxed mb-4">{member.details?.description || member.bio}</p>
                    <div className="flex flex-wrap justify-center gap-2 mb-4">
                      {member.details?.skills?.map((skill: string, idx: number) => (
                        <span key={idx} className="bg-primary/10 text-primary px-3 py-1 rounded-full text-xs font-semibold">{skill}</span>
                      ))}
                    </div>
                    <div className="flex justify-center gap-4 mb-2">
                      <a href={member.social.github} target="_blank" rel="noopener noreferrer" className="bg-muted p-2 rounded-lg hover:bg-primary hover:text-white transition-colors">
                        <Github className="w-5 h-5" />
                      </a>
                      <a href={member.social.twitter} target="_blank" rel="noopener noreferrer" className="bg-muted p-2 rounded-lg hover:bg-primary hover:text-white transition-colors">
                        <Twitter className="w-5 h-5" />
                      </a>
                      <a href={member.social.linkedin} target="_blank" rel="noopener noreferrer" className="bg-muted p-2 rounded-lg hover:bg-primary hover:text-white transition-colors">
                        <Linkedin className="w-5 h-5" />
                      </a>
                    </div>
                    {member.details?.email && (
                      <p className="text-xs text-muted-foreground text-center mt-2">Contacto: {member.details.email}</p>
                    )}
                  </>
                );
              })()}
            </motion.div>
          </motion.div>
        )}
      </AnimatePresence>
    </div>
  );
}
