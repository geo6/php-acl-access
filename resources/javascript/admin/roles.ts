"use strict";

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
import { submit as submitForm } from "./roles/form";
import rangeOnChange from "./roles/range";
import { load as loadPermInModal } from "./roles/permissions/modal";
import { submit as submitPermForm } from "./roles/permissions/form";

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

  /**
   * Submit form
   */
  if (document.querySelector("#modal-role form") !== null) {
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
  }

  /** Show permissions */
  document
    .querySelectorAll("a[href='#permissions']")
    .forEach((element: HTMLLinkElement) => {
      element.addEventListener("click", (event: Event) => {
        const target = event.currentTarget as HTMLLinkElement;
        const { id, name } = target.closest("tr").dataset;

        event.preventDefault();

        currentRoleId = parseInt(id);

        (document.querySelector(
          "#modal-permission-resource .modal-header > .modal-title > span"
        ) as HTMLSpanElement).innerText = name;

        loadPermInModal(apiAccessURL, currentRoleId);
      });
    });

  /** Apply permissions */
  document
    .querySelector("#modal-permission-resource form")
    .addEventListener("submit", (event: Event) => {
      const target = event.currentTarget as HTMLFormElement;

      event.preventDefault();

      submitPermForm(target, currentRoleId).then(() => {
        currentRoleId = null;

        $("#modal-permission-resource").modal("hide");
      });
    });
})();
