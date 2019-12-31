"use strict";

import { User } from "../../model/User";

import createUser from "./api/post";
import updateUser from "./api/put";

export function reset(form: HTMLFormElement): void {
  form
    .querySelectorAll("input[name='roles[]']")
    .forEach((input: HTMLInputElement) => {
      input.closest(".list-group-item").classList.remove("active");
    });
}

export async function submit(form: HTMLFormElement, id: number): Promise<User> {
  const data = new FormData(form);

  const user = new User();

  user.email = data.get("email").toString();
  user.fullname = data.get("fullname").toString();
  user.login = data.get("login").toString();

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
