"use strict";

import { User } from "../../../model/User";

import request from "../../../fetch";

export default async function(
  id: number,
  data: { user: User; roles: Array<number> }
): Promise<User> {
  return request("PUT", `${apiURL}/${id}`, data);
}
