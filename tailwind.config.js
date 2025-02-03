module.exports = {
  content: [
    "./assets/**/*.js",
    "./templates/**/*.html.twig",
  ],
  plugins: [require("daisyui")],

  daisyui: {
    themes: [
      "emerald",
      "halloween",
    ],
  },

}
