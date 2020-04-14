"use strict";

import "../../sass/admin.scss";

import $ from "jquery";

import "bootstrap/js/dist/dropdown";
import "bootstrap/js/dist/modal";
import "bootstrap/js/dist/popover";
import "bootstrap/js/dist/tab";
import "bootstrap/js/dist/util";

import getUser from "./api/get";
import deleteUser from "./api/delete";
import { init as initModal, load as loadUserInModal } from "./users/modal";
import { User } from "../model/User";
import { reset as resetForm, submit as submitForm } from "./users/form";
import { FormError, display as displayFormError } from "../global/FormError";

((): void => {
  let currentUserId: number = null;

  /**
   * Initialize modal
   */
  initModal();
  $("#modal-user").on("hide.bs.modal", () => {
    currentUserId = null;
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
   * Create a new user
   */
  if (document.getElementById("btn-create") !== null) {
    document.getElementById("btn-create").addEventListener("click", () => {
      const form = document.querySelector(
        "#modal-user form"
      ) as HTMLFormElement;

      form.reset();

      document.getElementById("btn-password").setAttribute("hidden", "");
      document.getElementById("btn-delete").setAttribute("hidden", "");

      $("#modal-user").modal("show");
    });
  }

  /**
   * Update a user
   */
  document
    .querySelectorAll("a[href='#edit']")
    .forEach((element: HTMLLinkElement) => {
      element.addEventListener("click", (event: Event) => {
        const target = event.currentTarget as HTMLLinkElement;
        const { id } = target.closest("tr").dataset;

        event.preventDefault();

        currentUserId = parseInt(id);

        getUser(parseInt(id)).then((json: User) => {
          loadUserInModal(json);

          document.getElementById("btn-password").removeAttribute("hidden");
          document.getElementById("btn-delete").removeAttribute("hidden");

          $("#details-tab").tab("show");

          $("#modal-user").modal("show");
        });
      });
    });

  /**
   * Delete a user
   */
  if (document.getElementById("btn-delete") !== null) {
    document.getElementById("btn-delete").addEventListener("click", () => {
      if (
        window.confirm("Are you sure you want to delete this user ?") === true
      ) {
        deleteUser(currentUserId).then(() => {
          currentUserId = null;

          document.location.reload();
        });
      }
    });
  }

  if (document.querySelector("#modal-user form") !== null) {
    /**
     * Submit form
     */
    document
      .querySelector("#modal-user form")
      .addEventListener("submit", (event: Event) => {
        const target = event.currentTarget as HTMLFormElement;

        event.preventDefault();

        submitForm(target, currentUserId).then(() => {
          currentUserId = null;

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
      .querySelector("#modal-user form")
      .addEventListener("reset", (event: Event) => {
        const target = event.currentTarget as HTMLFormElement;

        resetForm(target);
      });
  }
})();
