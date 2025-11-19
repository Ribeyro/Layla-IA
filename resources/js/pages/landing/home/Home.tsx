import React, { useEffect, useState } from "react";
import { motion, useAnimation } from "framer-motion";
import { useInView } from "react-intersection-observer";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { homeData } from "@/components/landing/data/homeData";
import { Smartphone, Zap, Palette, Search, BarChart2, Languages, UtensilsCrossed, CheckCircle2, Shield, CreditCard, ArrowUp } from "lucide-react";

const iconMap: Record<string, React.ElementType> = {
  Smartphone,
  Zap,
  Palette,
  Search,
  BarChart2,
  Languages,
  UtensilsCrossed,
  CheckCircle2,
  Shield,
  CreditCard,
};

export default function Home({ canRegister = true }: { canRegister?: boolean }) {
    // Animación para secciones al hacer scroll
    const [aboutRef, aboutInView] = useInView({ triggerOnce: false, threshold: 0.15 });
    const [projectRef, projectInView] = useInView({ triggerOnce: false, threshold: 0.15 });
    const [howRef, howInView] = useInView({ triggerOnce: false, threshold: 0.15 });
    const [featuresRef, featuresInView] = useInView({ triggerOnce: false, threshold: 0.15 });
    const [contactRef, contactInView] = useInView({ triggerOnce: false, threshold: 0.15 });
  const [showScrollTop, setShowScrollTop] = useState(false);

  useEffect(() => {
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add("show");
          } else {
            entry.target.classList.remove("show");
          }
        });
      },
      { threshold: 0.1, rootMargin: "-50px" }
    );

    document.querySelectorAll(".animate-on-scroll").forEach((el) => {
      observer.observe(el);
    });

    const handleScroll = () => {
      setShowScrollTop(window.scrollY > 400);
    };

    window.addEventListener("scroll", handleScroll);

    return () => {
      observer.disconnect();
      window.removeEventListener("scroll", handleScroll);
    };
  }, []);

  const scrollToTop = () => {
    window.scrollTo({ top: 0, behavior: "smooth" });
  };

  const [isDark, setIsDark] = useState(false);
  useEffect(() => {
    const updateTheme = () => {
      setIsDark(document.documentElement.classList.contains("dark"));
    };
    updateTheme();
    const observer = new MutationObserver(updateTheme);
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ["class"] });
    return () => observer.disconnect();
  }, []);
  const heroImage = isDark ? homeData.hero.image : homeData.hero.image;

  return (
    <div className="w-full bg-background relative">
      {/* HERO SECTION */}
      <section className="container mx-auto px-4 py-16 md:py-24 bg-background">
        <div className="flex flex-col lg:flex-row items-center gap-16">
          <div className="w-full lg:w-1/2 flex flex-col items-start space-y-6 animate-on-scroll fade-in-left">
            <div className="flex flex-wrap gap-3 mb-2">
              {homeData.hero.benefits.map((benefit, idx) => {
                const BenefitIcon = iconMap[benefit.icon] || CheckCircle2;
                return (
                  <Badge
                    key={idx}
                    variant="outline"
                    className="flex items-center gap-2 bg-background border-primary text-primary px-4 py-2 hover:bg-primary hover:text-white transition-all duration-300 select-none"
                  >
                    <BenefitIcon className="w-4 h-4" />
                    <span className="text-sm font-medium">{benefit.text}</span>
                  </Badge>
                );
              })}
            </div>

            <h1 className="text-4xl md:text-4xl lg:text-4xl font-black text-foreground leading-[1.05] select-none">
              {homeData.hero.title}
              <span className="block text-primary mt-2">
                {homeData.hero.highlight}
              </span>
            </h1>

            <h5 className="text-xl md:text-2xl font-semibold text-muted-foreground">
              {homeData.hero.subtitle}
            </h5>

            <p className="text-lg md:text-xl text-muted-foreground leading-relaxed">
              {homeData.hero.description}
            </p>

            <div className="flex flex-col sm:flex-row gap-4 w-full sm:w-auto pt-6">
              <Button
                asChild
                size="lg"
                className="bg-primary hover:bg-primary/80 text-white font-bold px-12 py-7 text-lg rounded-2xl shadow-lg hover:shadow-2xl transform hover:-translate-y-2 hover:scale-105 transition-all duration-300"
              >
                <a href={homeData.hero.cta.primary.href}>{homeData.hero.cta.primary.label}</a>
              </Button>
              <Button
                asChild
                variant="outline"
                size="lg"
                className="border-2 border-primary bg-background text-primary hover:bg-primary hover:text-white font-bold px-12 py-7 text-lg rounded-2xl shadow-lg hover:shadow-2xl transform hover:-translate-y-2 hover:scale-105 transition-all duration-300"
              >
                <a href={homeData.hero.cta.secondary.href}>{homeData.hero.cta.secondary.label}</a>
              </Button>
            </div>
          </div>

          <div className="w-full lg:w-1/2 flex justify-center lg:justify-end animate-on-scroll fade-in-right">
            <div className="relative w-full max-w-[550px]">
              <div className="relative">
                <img
                  src={heroImage}
                  alt="Layla IA Hero"
                  className="w-full h-auto object-cover transform hover:scale-105 transition-transform duration-500 select-none"
                  style={{ border: "none", borderRadius: 0, boxShadow: "none" }}
                  draggable={false}
                  onContextMenu={(e) => e.preventDefault()}
                />
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* ABOUT SECTION */}
      <motion.section
        ref={aboutRef}
        className="w-full py-10 bg-muted"
        data-section="about"
        initial={{ opacity: 0, y: 40 }}
        animate={aboutInView ? { opacity: 1, y: 0 } : {}}
        transition={{ duration: 0.7, ease: "easeOut" }}
      >
        <div className="w-full flex flex-col items-center">
          <div className="w-full max-w-3xl text-center mb-8 animate-on-scroll fade-in-up">
            <h2 className="text-3xl font-bold text-foreground mb-3 select-none tracking-tight">
              {homeData.about.title}
            </h2>
            <p className="text-lg text-muted-foreground leading-relaxed font-normal mb-2">
              {homeData.about.description}
            </p>
          </div>
          <div className="w-full max-w-5xl grid grid-cols-1 md:grid-cols-3 gap-6">
            {homeData.about.cards.map((card, idx) => {
              const CardIcon = iconMap[card.icon] || UtensilsCrossed;
              return (
                <motion.div
                  key={idx}
                  className="flex justify-center items-center"
                  initial={{ opacity: 0, scale: 0.9, rotateY: 30 }}
                  animate={aboutInView ? { opacity: 1, scale: 1, rotateY: 0 } : {}}
                  transition={{ duration: 0.7, ease: "easeOut", delay: idx * 0.1 }}
                >
                  <Card className="bg-white border-0 shadow-lg rounded-2xl flex flex-col items-center py-6 px-5 h-full w-full">
                    <div className="mb-3 flex items-center justify-center">
                      <div className="w-12 h-12 bg-muted rounded-xl flex items-center justify-center border border-gray-200">
                        <CardIcon className="w-7 h-7 text-primary" />
                      </div>
                    </div>
                    <CardTitle className="text-center text-lg font-semibold text-foreground mb-2 select-none">
                      {card.title}
                    </CardTitle>
                    <CardContent>
                      <p className="text-muted-foreground text-center text-base leading-relaxed font-normal">
                        {card.description}
                      </p>
                    </CardContent>
                  </Card>
                </motion.div>
              );
            })}
          </div>
        </div>
      </motion.section>

      {/* HOW IT WORKS SECTION */}
      <motion.section
        ref={howRef}
        className="py-20 md:py-28 bg-primary text-white relative overflow-hidden"
        initial={{ opacity: 0, y: 40 }}
        animate={howInView ? { opacity: 1, y: 0 } : {}}
        transition={{ duration: 0.7, ease: "easeOut" }}
      >
        <div className="container mx-auto px-4 relative z-10">
          <div className="text-center mb-16 animate-on-scroll fade-in-up">
            <h2 className="text-3xl font-bold mb-6 select-none">
              ¿Cómo funciona?
            </h2>
          </div>
          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            {homeData.howItWorks.map((step, idx) => (
              <motion.div
                key={idx}
                className="flex justify-center items-center"
                initial={{ opacity: 0, scale: 0.9, rotateY: 30 }}
                animate={howInView ? { opacity: 1, scale: 1, rotateY: 0 } : {}}
                transition={{ duration: 0.7, ease: "easeOut", delay: idx * 0.1 }}
              >
                <Card className="bg-white/10 backdrop-blur-lg border-white/20 text-white hover:bg-white/20 hover:scale-105 hover:-translate-y-2 transition-all duration-300 h-full shadow-2xl cursor-pointer w-full">
                  <CardContent className="p-8">
                    <div className="w-14 h-14 bg-white rounded-2xl flex items-center justify-center mb-6">
                      <span className="text-2xl font-bold text-primary">{idx + 1}</span>
                    </div>
                    <h5 className="text-lg font-bold mb-4 text-white select-none">{step.step}</h5>
                    <p className="leading-relaxed text-white/90 text-base">{step.description}</p>
                  </CardContent>
                </Card>
              </motion.div>
            ))}
          </div>
        </div>
      </motion.section>

      {/* FEATURES SECTION */}
      <motion.section
        ref={featuresRef}
        id="features"
        className="py-20 md:py-28 bg-background"
        initial={{ opacity: 0, y: 40 }}
        animate={featuresInView ? { opacity: 1, y: 0 } : {}}
        transition={{ duration: 0.7, ease: "easeOut" }}
      >
        <div className="container mx-auto px-4">
          <div className="text-center mb-16 animate-on-scroll fade-in-up">
            <h2 className="text-3xl font-bold text-foreground mb-6 select-none">
              Características Principales
            </h2>
            <p className="text-lg text-muted-foreground">
              Todo lo que necesitas para tu vida académica y emocional
            </p>
          </div>
          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            {homeData.features.map((feature, idx) => {
              const LucideIcon = iconMap[feature.icon] || Smartphone;
              return (
                <motion.div
                  key={idx}
                  className="flex justify-center items-center"
                  initial={{ opacity: 0, scale: 0.9, rotateY: 30 }}
                  animate={featuresInView ? { opacity: 1, scale: 1, rotateY: 0 } : {}}
                  transition={{ duration: 0.7, ease: "easeOut", delay: idx * 0.1 }}
                >
                  <Card className="rounded-2xl bg-background border-2 border-muted shadow-lg hover:shadow-2xl hover:scale-105 hover:-translate-y-4 hover:border-primary transition-all duration-300 h-full group cursor-pointer w-full">
                    <CardHeader>
                      <div className="mb-4 flex justify-center">
                        <div className="w-12 h-12 bg-muted group-hover:bg-primary rounded-xl flex items-center justify-center transition-all duration-300">
                          <LucideIcon className="w-7 h-7 text-primary group-hover:text-white transition-colors duration-300" />
                        </div>
                      </div>
                      <CardTitle className="text-center text-lg font-bold select-none">{feature.title}</CardTitle>
                    </CardHeader>
                    <CardContent>
                      <p className="text-muted-foreground text-center leading-relaxed text-base">{feature.description}</p>
                    </CardContent>
                  </Card>
                </motion.div>
              );
            })}
          </div>
        </div>
      </motion.section>

      {/* CONTACT SECTION */}
      <motion.section
        ref={contactRef}
        className="py-20 md:py-28 bg-primary relative overflow-hidden"
        data-section="contact"
        initial={{ opacity: 0, y: 40 }}
        animate={contactInView ? { opacity: 1, y: 0 } : {}}
        transition={{ duration: 0.7, ease: "easeOut" }}
      >
        <div className="container mx-auto px-4 text-center relative z-10 animate-on-scroll fade-in-up">
          <h2 className="text-4xl md:text-4xl lg:text-4xl font-black text-white mb-8 select-none">
            {homeData.contact.title}
          </h2>
          <p className="text-xl md:text-2xl text-white/90 mb-12 max-w-3xl mx-auto leading-relaxed">
            {homeData.contact.description}
          </p>
          {canRegister && (
            <Button
              asChild
              size="lg"
              className="bg-white hover:bg-muted text-primary font-black px-14 py-8 text-xl rounded-2xl shadow-2xl hover:shadow-3xl transform hover:scale-110 hover:-translate-y-2 transition-all duration-300"
            >
              <a href={homeData.contact.cta.href}>{homeData.contact.cta.label}</a>
            </Button>
          )}
        </div>
      </motion.section>

      {showScrollTop && (
        <Button
          onClick={scrollToTop}
          className="fixed bottom-8 right-8 w-14 h-14 rounded-full bg-primary hover:bg-primary/80 text-white shadow-2xl hover:shadow-3xl transform hover:scale-110 hover:-translate-y-1 transition-all duration-300 z-50"
          aria-label="Volver arriba"
        >
          <ArrowUp className="w-6 h-6" />
        </Button>
      )}
    </div>
  );
}
