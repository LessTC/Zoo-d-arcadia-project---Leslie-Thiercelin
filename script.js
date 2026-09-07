/**
 * Le seul JavaScript maison du site.
 *
 * Tout le reste de l'interactivité vient du composant Collapse de Bootstrap,
 * réutilisé pour le menu burger, le dépliement des noms d'animaux sur
 * l'accueil et les onglets des espaces professionnels.
 *
 * Ce fichier contenait auparavant deux simulations écrites à l'époque du
 * site statique : un faux envoi de formulaire et un faux ajout de repas
 * dans le tableau d'alimentation. Elles ont été retirées après la migration
 * vers PHP : ces deux traitements se font désormais côté serveur, avec
 * persistance en base, et le code correspondant ne s'exécutait plus jamais.
 */

// Année courante dans le pied de page, pour ne pas avoir à la modifier
// chaque 1er janvier.
document.addEventListener("DOMContentLoaded", () => {
  const yearSpan = document.querySelector("#year");

  if (yearSpan) {
    yearSpan.textContent = new Date().getFullYear();
  }
});
