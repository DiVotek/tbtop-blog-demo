import "../css/admin.css";

import { createInertiaApp } from "@inertiajs/react";
import { AdminErrorPage } from "@tbtop/inertia-admin";
import { createRoot } from "react-dom/client";

const appName = import.meta.env.VITE_APP_NAME || "Laravel";

createInertiaApp({
	title: (title) => `${title} - ${appName}`,
	resolve: async (name) => {
		if (name === "admin/page") {
			const mod = await import("@tbtop/inertia-admin");
			return { default: mod.AdminPage };
		}
		if (name === "admin/error") {
			return { default: AdminErrorPage };
		}
		throw new Error(`[admin] No component found for page: ${name}`);
	},
	setup({ el, App, props }) {
		const root = createRoot(el);
		root.render(<App {...props} />);
	},
	progress: {
		color: "#4B5563",
	},
});
