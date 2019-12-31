"use strict";

import $ from "jquery";

import "bootstrap/js/dist/modal";
import "bootstrap/js/dist/tab";
import "bootstrap/js/dist/util";

import getRole from "./api/get";
import deleteRole from "./api/delete";
import { load as loadRoleInModal } from "./roles/modal";
import { Role } from "../model/Role";
import { submit as submitForm } from "./roles/form";
import rangeOnChange from "./roles/range";

((): void => {
  let currentRoleId: number = null;

  /**
   * Range input
   */
  document
    .querySelector("#inputPriority")
    .addEventListener("change", (event: InputEvent) => {
      const target = event.currentTarget as HTMLInputElement;

      rangeOnChange(target);
    });

  /**
   * Create a new role
   */
  document.getElementById("btn-create").addEventListener("click", () => {
    const form = document.querySelector("#modal-role form") as HTMLFormElement;

    form.reset();

    rangeOnChange(form.querySelector("input[name=priority]"));

    document.getElementById("btn-delete").setAttribute("hidden", "");

    $("#modal-role").modal("show");
  });

  /**
   * Update a role
   */
  document
    .querySelectorAll("a[href='#edit']")
    .forEach((element: HTMLLinkElement) => {
      element.addEventListener("click", (event: Event) => {
        const target = event.currentTarget as HTMLLinkElement;
        const { id } = target.closest("tr").dataset;

        event.preventDefault();

        currentRoleId = parseInt(id);

        getRole(parseInt(id)).then((json: Role) => {
          loadRoleInModal(json);

          document.getElementById("btn-delete").removeAttribute("hidden");

          $("#modal-role").modal("show");
        });
      });
    });

  /**
   * Delete a role
   */
  document.getElementById("btn-delete").addEventListener("click", () => {
    if (
      window.confirm("Are you sure you want to delete this role ?") === true
    ) {
      deleteRole(currentRoleId).then(() => {
        currentRoleId = null;

        document.location.reload();
      });
    }
  });

  /**
   * Submit form
   */
  document
    .querySelector("#modal-role form")
    .addEventListener("submit", (event: Event) => {
      const target = event.currentTarget as HTMLFormElement;

      event.preventDefault();

      submitForm(target, currentRoleId).then(() => {
        currentRoleId = null;

        document.location.reload();
      });
    });
})();
