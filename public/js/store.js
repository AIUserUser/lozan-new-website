document.addEventListener("DOMContentLoaded", () => {
  const burger = document.querySelector("[data-menu-toggle]");
  const row = document.getElementById("lozan-mobile-menu");
  const scrim = document.querySelector("[data-menu-close]");
  const setOpen = (open) => {
    if (!burger || !row) return;
    row.classList.toggle("header__row--open", open);
    burger.classList.toggle("header__burger--on", open);
    burger.setAttribute("aria-expanded", open ? "true" : "false");
    document.body.classList.toggle("lozan-no-scroll", open);
    if (scrim) scrim.hidden = !open;
  };
  burger?.addEventListener("click", () => setOpen(!row.classList.contains("header__row--open")));
  scrim?.addEventListener("click", () => setOpen(false));
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") setOpen(false);
  });

  document.querySelectorAll("[data-thumb]").forEach((btn) => {
    btn.addEventListener("click", () => {
      const img = document.getElementById("product-main-img");
      if (img) img.src = btn.getAttribute("data-thumb");
      document.querySelectorAll("[data-thumb]").forEach((b) => b.classList.remove("product__thumb--on"));
      btn.classList.add("product__thumb--on");
    });
  });

  document.querySelectorAll("[data-qty-delta]").forEach((btn) => {
    btn.addEventListener("click", () => {
      const input = btn.parentElement.querySelector("input[name=qty], .qty__input");
      if (!input) return;
      const next = Math.min(99, Math.max(1, Number(input.value || 1) + Number(btn.getAttribute("data-qty-delta"))));
      input.value = next;
    });
  });

  // Shop filter dropdowns: one open at a time; close on outside click or Escape.
  const drops = [...document.querySelectorAll("[data-sf-drop]")];
  drops.forEach((drop) => {
    drop.addEventListener("toggle", () => {
      if (drop.open) drops.forEach((other) => { if (other !== drop) other.open = false; });
    });
  });
  document.addEventListener("click", (e) => {
    drops.forEach((drop) => { if (drop.open && !drop.contains(e.target)) drop.open = false; });
  });
  document.addEventListener("keydown", (e) => {
    if (e.key !== "Escape") return;
    const open = drops.find((drop) => drop.open);
    if (open) {
      open.open = false;
      open.querySelector("summary").focus();
    }
  });

  const delivery = document.getElementById("delivery");
  const addressField = document.getElementById("address-field");
  if (delivery && addressField) {
    const sync = () => {
      addressField.style.display = delivery.checked ? "" : "none";
    };
    delivery.addEventListener("change", sync);
    sync();
  }
});
