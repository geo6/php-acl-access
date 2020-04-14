"use strict";

import "../../sass/admin.scss";

import $ from "jquery";

import "bootstrap/js/dist/dropdown";
import "bootstrap/js/dist/modal";
import "bootstrap/js/dist/popover";
import "bootstrap/js/dist/tab";
import "bootstrap/js/dist/util";

import getRole from "./api/get";
import deleteRole from "./api/delete";
import { load as loadRoleInModal } from "./roles/modal";
import { Role } from "../model/Role";
import { resetWarning, submit as submitForm } from "./roles/form";
import rangeOnChange from "./roles/range";
import { load as loadPermInModal } from "./roles/access/modal";
import { submit as submitPermForm } from "./roles/access/form";
import { FormError, display as displayFormError } from "../global/FormError";

((): void => {
  let currentRoleId: number = null;

  /**
   * Initialize modal
   */
  $("#modal-role").on("hide.bs.modal", () => {
    currentRoleId = null;
  });

  /**
   * Initialize popover
   */
  document
    .querySelectorAll("[data-toggle='popover']")
    .forEach((element: HTMLLinkElement) => {
      $(element).popover();
    });

  /**
   * Create a new role
   */
  if (document.getElementById("btn-create") !== null) {
    document.getElementById("btn-create").addEventListener("click", () => {
      const form = document.querySelector(
        "#modal-role form"
      ) as HTMLFormElement;

      form.reset();

      rangeOnChange(form.querySelector("input[name=priority]"));

      document.getElementById("btn-delete").setAttribute("hidden", "");

      $("#modal-role").modal("show");
    });
  }

  /**
   * Range input
   */
  if (document.querySelector("#inputPriority") !== null) {
    document
      .querySelector("#inputPriority")
      .addEventListener("change", (event: InputEvent) => {
        const target = event.currentTarget as HTMLInputElement;

        rangeOnChange(target);
      });
  }

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
  if (document.getElementById("btn-delete") !== null) {
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
  }

  if (document.querySelector("#modal-role form") !== null) {
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
        }).catch((error: FormError|Error) => {
          if (error.name === "FormError") {
            displayFormError(target, error as FormError);
          } else {
            const alert = target.querySelector(".alert") as HTMLDivElement;

            alert.innerText = error.message;
            alert.hidden = false;
          }
        });
      });

    /**
     * Reset form
     */
    document
      .querySelector("#modal-role form")
      .addEventListener("reset", (event: Event) => {
        const target = event.currentTarget as HTMLFormElement;

        resetWarning(target);
      });
  }

  /** Show access */
  document
    .querySelectorAll("a[href='#access']")
    .forEach((element: HTMLLinkElement) => {
      element.addEventListener("click", (event: Event) => {
        const target = event.currentTarget as HTMLLinkElement;
        const { id, name } = target.closest("tr").dataset;

        event.preventDefault();

        currentRoleId = parseInt(id);

        (document.querySelector(
          "#modal-access-resource .modal-header > .modal-title > span"
        ) as HTMLSpanElement).innerText = name;

        loadPermInModal(apiAccessURL, currentRoleId);
      });
    });

  /** Apply access */
  document
    .querySelector("#modal-access-resource form")
    .addEventListener("submit", (event: Event) => {
      const target = event.currentTarget as HTMLFormElement;

      event.preventDefault();

      submitPermForm(target, currentRoleId).then(() => {
        currentRoleId = null;

        $("#modal-access-resource").modal("hide");
      });
    });
})();
