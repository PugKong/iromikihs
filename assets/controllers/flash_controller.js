import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
  connect() {
    const flashes = this.element.getElementsByClassName("flash");
    for (const flash of flashes) {
      flash.classList.add("cursor-pointer");
    }
  }

  destroy(event) {
    event.target.remove();
  }
}
