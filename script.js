/**
 * Le seul JavaScript maison du site.
 *
 * Le reste de l'interactivité vient de composants Bootstrap déclarés dans
 * le HTML : Collapse (menu burger, noms d'animaux sur l'accueil), Tab
 * (onglets des espaces professionnels) et Modal (fiche animal).
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

/**
 * Fiche animal en modale (US 4 — requête asynchrone).
 *
 * Au clic sur le nom d'un animal, on empêche la navigation, on demande ses
 * données à api/animal.php au format JSON, et on remplit la modale. Le
 * visiteur reste sur la page de l'habitat : rien n'est rechargé.
 */
document.addEventListener("DOMContentLoaded", () => {
  const modaleElement = document.querySelector("#modaleAnimal");

  if (!modaleElement) {
    return; // Page sans modale (accueil, contact…) : rien à brancher.
  }

  const modale = new bootstrap.Modal(modaleElement);
  const message = modaleElement.querySelector("#modaleAnimalMessage");
  const contenu = modaleElement.querySelector("#modaleAnimalContenu");
  const lienComplet = modaleElement.querySelector("#modaleAnimalLien");

  document.querySelectorAll("a[data-animal]").forEach((lien) => {
    lien.addEventListener("click", async (evenement) => {
      evenement.preventDefault();

      message.textContent = "Chargement…";
      message.hidden = false;
      contenu.hidden = true;
      lienComplet.href = lien.href;
      modale.show();

      try {
        const reponse = await fetch(
          "api/animal.php?id=" + encodeURIComponent(lien.dataset.animal),
        );

        // fetch() ne lève pas d'erreur sur un 404 ou un 500 : il faut
        // contrôler reponse.ok soi-même.
        if (!reponse.ok) {
          throw new Error("Réponse " + reponse.status);
        }

        remplirModale(await reponse.json());
      } catch (erreur) {
        message.textContent =
          "La fiche n’a pas pu être chargée. Utilisez le lien ci-dessous.";
      }
    });
  });

  /**
   * textContent, jamais innerHTML : le texte reçu est inséré comme du texte
   * et n'est jamais interprété comme du HTML. C'est la protection contre le
   * XSS côté JavaScript, l'équivalent de htmlspecialchars() côté PHP.
   */
  function remplirModale(animal) {
    modaleElement.querySelector("#modaleAnimalTitre").textContent = animal.nom;
    modaleElement.querySelector("#modaleAnimalEspece").textContent =
      animal.espece;
    modaleElement.querySelector("#modaleAnimalDescription").textContent =
      animal.description;
    modaleElement.querySelector("#modaleAnimalHabitat").textContent =
      animal.habitat;
    modaleElement.querySelector("#modaleAnimalNourriture").textContent =
      animal.nourriture;
    modaleElement.querySelector("#modaleAnimalEtat").textContent = animal.etat;

    const image = modaleElement.querySelector("#modaleAnimalImage");
    image.hidden = !animal.image;
    if (animal.image) {
      image.src = animal.image;
      image.alt = animal.imageAlt || "";
    }

    const rapport = modaleElement.querySelector("#modaleAnimalRapport");
    rapport.textContent = animal.rapport
      ? "Dernier passage du vétérinaire le " +
        animal.rapport.date +
        " — " +
        animal.rapport.etat
      : "";

    message.hidden = true;
    contenu.hidden = false;
  }
});
