import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";

/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
    "./storage/framework/views/*.php",
    "./resources/views/**/*.blade.php",
  ],

  darkMode: "class",

  theme: {
    extend: {
      fontFamily: {
        sans: ["Figtree", ...defaultTheme.fontFamily.sans],
      },
      colors: {
        bodybg: "#18181b",
        lightbg: "#fdf9e5",
        bodybg2: "#27272a",
        lightbg2: "#eeead9",
        primary: "#fdab14",
        primaryHover: "#ffb42a",
        danger: "#f44228",
        dangerHover: "#f65a43",
        warning: "#0b6cb6",
        warningHover: "#1d7bc3",
      },
    },
  },

  plugins: [forms],
};
