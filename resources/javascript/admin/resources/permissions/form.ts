"use strict";

import request from "../../../fetch";

export async function submit(form: HTMLFormElement, id: number): Promise<void> {
  const data = new FormData(form);

  const allow = Array.from(data.entries()).filter(
    (entry: [string, FormDataEntryValue]) => parseInt(entry[1] as string) === 1
  );

  return request(
    "PUT",
    `${apiAccessURL}/resource/${id}`,
    Object.fromEntries(allow)
  );
}
