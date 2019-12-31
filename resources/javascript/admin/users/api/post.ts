"use strict";

import { User } from "../../../model/User";

import request from "../../../fetch";

export default async function(data: {
  user: User;
  roles: Array<number>;
}): Promise<User> {
  return request("POST", `${apiURL}`, data);
}
