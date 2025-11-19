import React from 'react';
import Header from "@/components/landing/header/Header";
import Navbar from "@/components/landing/navbar/Navbar";
import Footer from "@/components/landing/footer/Footer";
import Home from "@/pages/landing/home/Home";

export default function Content({ children }: { children?: React.ReactNode }) {
  return (
    <div className="flex flex-col bg-background">
      <Header />
      <Navbar />
      <main className="flex-1 w-full px-0 py-0">
        {children}
      </main>
      <Footer />
    </div>
  );
}
