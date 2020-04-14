"use strict";

import "../../sass/admin.scss";

import $ from "jquery";

import "bootstrap/js/dist/dropdown";
import "bootstrap/js/dist/modal";
import "bootstrap/js/dist/tab";
import "bootstrap/js/dist/util";

import getResource from "./api/get";
import deleteResource from "./api/delete";
import { load as loadResourceInModal } from "./resources/modal";
import { Resource } from "../model/Resource";
import { submit as submitForm, resetWarning } from "./resources/form";
import { load as loadPermInModal } from "./resources/access/modal";
import { submit as submitPermForm } from "./resources/access/form";
import { FormError, display as displayFormError } from "../global/FormError";

((): void => {
  let currentResourceId: number = null;

  /**
   * Initialize modal
   */
  $("#modal-resource").on("hide.bs.modal", () => {
    currentResourceId = null;
  });

  /**
   * Create a new resource
   */
  if (document.getElementById("btn-create") !== null) {
    document.getElementById("btn-create").addEventListener("click", () => {
      const form = document.querySelector(
        "#modal-resource form"
      ) as HTMLFormElement;

      form.reset();

      document.getElementById("btn-delete").setAttribute("hidden", "");

      $("#modal-resource").modal("show");
    });
  }

  /**
   * Update a resource
   */
  document
    .querySelectorAll("a[href='#edit']")
    .forEach((element: HTMLLinkElement) => {
      element.addEventListener("click", (event: Event) => {
        const target = event.currentTarget as HTMLLinkElement;
        const { id } = target.closest("tr").dataset;

        event.preventDefault();

        currentResourceId = parseInt(id);

        getResource(parseInt(id)).then((json: Resource) => {
          loadResourceInModal(json);

          document.getElementById("btn-delete").removeAttribute("hidden");

          $("#modal-resource").modal("show");
        });
      });
    });

  /**
   * Delete a resource
   */
  if (document.getElementById("btn-delete") !== null) {
    document.getElementById("btn-delete").addEventListener("click", () => {
      if (
        window.confirm("Are you sure you want to delete this resource ?") ===
        true
      ) {
        deleteResource(currentResourceId).then(() => {
          currentResourceId = null;

          document.location.reload();
        });
      }
    });
  }

  if (document.querySelector("#modal-resource form") !== null) {
    /**
     * Submit form
     */
    document
      .querySelector("#modal-resource form")
      .addEventListener("submit", (event: Event) => {
        const target = event.currentTarget as HTMLFormElement;

        event.preventDefault();

        submitForm(target, currentResourceId).then(() => {
          currentResourceId = null;

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
      .querySelector("#modal-resource form")
      .addEventListener("reset", (event: Event) => {
        const target = event.currentTarget as HTMLFormElement;

        resetWarning(target);
      });
  }

  /**
   * Show access
   */
  document
    .querySelectorAll("a[href='#access']")
    .forEach((element: HTMLLinkElement) => {
      element.addEventListener("click", (event: Event) => {
        const target = event.currentTarget as HTMLLinkElement;
        const { id, name } = target.closest("tr").dataset;

        event.preventDefault();

        currentResourceId = parseInt(id);

        (document.querySelector(
          "#modal-access-role .modal-header > .modal-title > span"
        ) as HTMLSpanElement).innerText = name;

        loadPermInModal(apiAccessURL, currentResourceId);
      });
    });

  /** Apply access */
  document
    .querySelector("#modal-access-role form")
    .addEventListener("submit", (event: Event) => {
      const target = event.currentTarget as HTMLFormElement;

      event.preventDefault();

      submitPermForm(target, currentResourceId).then(() => {
        currentResourceId = null;

        $("#modal-access-role").modal("hide");
      });
    });
})();
