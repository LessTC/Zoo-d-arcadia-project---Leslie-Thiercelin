/**
Année courante dans le pied de page
*/ 
document.addEventListener("DOMContentLoaded", () => {
  const yearSpan = document.querySelector("#year");

  if (yearSpan) {
    yearSpan.textContent = new Date().getFullYear();
  }
});

/**
 * Fiche animal en modale (US 4 — requête asynchrone).
 */
document.addEventListener("DOMContentLoaded", () => {
  const modaleElement = document.querySelector("#modaleAnimal");

  if (!modaleElement) {
    return; // Page sans modale (accueil, contact…)
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
   * textContent, jamais innerHTML : Protection contre le XSS côté JavaScript */
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
