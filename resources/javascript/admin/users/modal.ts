"use strict";

import { User } from "../../model/User";
import { Role } from "../../model/Role";

function highlight(input: HTMLInputElement): void {
  const listItem = input.closest(".list-group-item");

  if (input.checked === true) {
    listItem.classList.add("active");
  } else {
    listItem.classList.remove("active");
  }
}

export function init(): void {
  document
    .querySelectorAll("#modal-user form input[name='roles[]']")
    .forEach((input: HTMLInputElement) => {
      input.addEventListener("change", (event: Event) => {
        const target = event.currentTarget as HTMLInputElement;

        highlight(target);
      });
    });
}

export function load(user: User): void {
  const form = document.querySelector("#modal-user form") as HTMLFormElement;

  form.reset();

  (form.querySelector("input[name='login']") as HTMLInputElement).value =
    user.login;

  (form.querySelector("input[name='email']") as HTMLInputElement).value =
    user.email;

  (form.querySelector("input[name='fullname']") as HTMLInputElement).value =
    user.fullname;

  if (user.redirect !== null) {
    (form.querySelector(
      "select[name='redirect']"
    ) as HTMLSelectElement).value = user.redirect.toString();
  }

  user.roles.forEach((role: Role) => {
    const input = form.querySelector(
      `input[name='roles[]'][value='${role.id}']`
    ) as HTMLInputElement;

    input.checked = true;

    highlight(input);
  });
}
