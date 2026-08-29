// 1) Mettre automatiquement l'année dans le footer
document.addEventListener("DOMContentLoaded", () => {
  const yearSpan = document.querySelector("#year");
  if (yearSpan) {
    yearSpan.textContent = new Date().getFullYear();
  }
});

// 2) Simulation d'envoi — uniquement pour les formulaires pas encore
// branchés au serveur, reconnaissables à leur attribut action vide ou "#".
// Les formulaires réellement traités en PHP ont une action pointant vers
// une page : on les laisse partir normalement.
document.addEventListener("submit", (e) => {
  const form = e.target;
  if (!form.matches("form")) return;

  const action = form.getAttribute("action");
  const estUneMaquette = !action || action === "#";

  if (!estUneMaquette) {
    return; // vrai formulaire : PHP prend le relais
  }

  e.preventDefault();

  if (!form.checkValidity()) {
    alert("Merci de remplir tous les champs obligatoires.");
    return;
  }

  alert("Formulaire envoyé (simulation).");
  form.reset();
});

// 5) Ajout simple d’alimentation (Dashboard employé)
document.addEventListener("submit", (e) => {
  if (!e.target.matches("#form-alimentation")) return;

  e.preventDefault();

  const form = e.target;
  const date = form.querySelector("#date")?.value;
  const heure = form.querySelector("#heure")?.value;
  const animal = form.querySelector("#animal")?.value;
  const nourriture = form.querySelector("#food")?.value;
  const quantite = form.querySelector("#quantity")?.value;

  if (!date || !heure || !animal || !nourriture || !quantite) {
    alert("Merci de remplir tous les champs.");
    return;
  }

  const tbody = document.querySelector("#table-alimentation tbody");
  if (tbody) {
    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td>${date} ${heure}</td>
      <td>${animal}</td>
      <td>${nourriture}</td>
      <td>${quantite}</td>
    `;
    tbody.appendChild(tr);
  }

  form.reset();
});
