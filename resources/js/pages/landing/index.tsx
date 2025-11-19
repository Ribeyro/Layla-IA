import Content from "./Content";
import { motion } from "framer-motion";
import Home from "./home/Home";
import AboutMe from "./about-me/AboutMe";
import Contact from "./contact/Contact";
import React, { useEffect, useState } from "react";

export default function LandingPage() {
	const [path, setPath] = useState(typeof window !== "undefined" ? window.location.pathname : "/");

	useEffect(() => {
		const onPopState = () => setPath(window.location.pathname);
		window.addEventListener("popstate", onPopState);
		return () => window.removeEventListener("popstate", onPopState);
	}, []);

	let page;
	if (path === "/about-us") {
		page = <AboutMe />;
	} else if (path === "/contact") {
		page = <Contact />;
	} else {
		page = <Home />;
	}

	return (
		<motion.div
			initial={{ opacity: 0, y: 40 }}
			animate={{ opacity: 1, y: 0 }}
			transition={{ duration: 0.7, ease: "easeOut" }}
		>
			<Content>{page}</Content>
		</motion.div>
	);
}
