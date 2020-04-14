"use strict";

import { User } from "../../model/User";

import createUser from "./api/post";
import updateUser from "./api/put";

function resetWarning(form: HTMLFormElement): void {
  (form.querySelector(".alert") as HTMLDivElement).hidden = true;

  form.querySelectorAll(".form-control.is-invalid").forEach((element: HTMLInputElement|HTMLSelectElement) => element.classList.remove("is-invalid"));
  form.querySelectorAll(".invalid-feedback").forEach((element: HTMLDivElement) => element.remove());
}

export function reset(form: HTMLFormElement): void {
  resetWarning(form);

  form
    .querySelectorAll("input[name='roles[]']")
    .forEach((input: HTMLInputElement) => {
      input.closest(".list-group-item").classList.remove("active");
    });
}

export async function submit(form: HTMLFormElement, id: number): Promise<User> {
  resetWarning(form);

  const data = new FormData(form);

  const user = new User();

  user.email = data.get("email").toString();
  user.fullname = data.get("fullname").toString();
  user.login = data.get("login").toString();
  user.redirect = parseInt(data.get("redirect").toString());

  const roles = [];
  data.getAll("roles[]").forEach((value: FormDataEntryValue) => {
    roles.push(parseInt(value.toString()));
  });

  if (id !== null) {
    return updateUser(id, { user, roles });
  } else {
    return createUser({ user, roles });
  }
}
