import React, { useState } from "react";

export default function Contact() {
  const [form, setForm] = useState({ name: "", email: "", message: "" });
  const [submitted, setSubmitted] = useState(false);

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    setForm({ ...form, [e.target.name]: e.target.value });
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    // Aquí podrías enviar el formulario a tu backend
    setSubmitted(true);
  };

  return (
    <div className="w-full min-h-full bg-background flex flex-col items-center justify-center py-16 px-4">
      <div className="max-w-2xl w-full bg-white/80 rounded-2xl shadow-lg p-8 flex flex-col items-center">
        <img
          src="/landing/logo_negro.png"
          alt="Contáctanos"
          className="w-48 h-48 mb-0 object-contain"
        />
        <h1 className="text-3xl font-bold text-primary mb-2 text-center">Contáctanos</h1>
        <p className="text-muted-foreground text-center mb-8">
          ¿Tienes dudas, sugerencias o quieres colaborar? Completa el formulario y nos pondremos en contacto contigo.
        </p>
        {submitted ? (
          <div className="text-center text-primary font-semibold py-8">
            ¡Gracias por contactarnos! Te responderemos pronto.
          </div>
        ) : (
          <form className="w-full flex flex-col gap-5" onSubmit={handleSubmit}>
            <input
              type="text"
              name="name"
              placeholder="Tu nombre"
              value={form.name}
              onChange={handleChange}
              required
              className="border border-muted rounded-lg px-4 py-3 text-base focus:outline-none focus:border-primary"
            />
            <input
              type="email"
              name="email"
              placeholder="Tu correo electrónico"
              value={form.email}
              onChange={handleChange}
              required
              className="border border-muted rounded-lg px-4 py-3 text-base focus:outline-none focus:border-primary"
            />
            <textarea
              name="message"
              placeholder="Tu mensaje"
              value={form.message}
              onChange={handleChange}
              required
              rows={5}
              className="border border-muted rounded-lg px-4 py-3 text-base focus:outline-none focus:border-primary resize-none"
            />
            <button
              type="submit"
              className="bg-primary text-white font-bold py-3 rounded-lg hover:bg-primary/80 transition-colors"
            >
              Enviar mensaje
            </button>
          </form>
        )}
      </div>
    </div>
  );
}
