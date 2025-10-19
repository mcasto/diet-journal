import { useStore } from "src/stores/store";
import wretch from "wretch";

export default async ({ path, method, payload, useAuth = false }) => {
  const store = useStore();

  // Initialize the base request
  let request = useAuth
    ? wretch("/api").auth(`Bearer ${store.user.token}`)
    : wretch("/api");

  // Handle GET vs. other methods
  if (method === "get" && payload) {
    // Append payload to path for GET (e.g., /api/test/1)

    const payloadValue = Object.values(payload).shift();

    const url = `${path}/${payloadValue}`;

    request = request.url(url);
  } else {
    // For non-GET methods, use payload as the body
    request = request.url(path);
    if (payload) {
      request = request.json(payload); // Set JSON body
    }
  }

  // Execute the request
  // return request[method]().json();

  const response = await request[method]();

  return response
    .error(422, (error) => {
      return error.json;
    })
    .json();
};
