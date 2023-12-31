/****
 *  web.js - a simple "Like" plugin using redis and fetch (javascript)
 *  Copyright 2023 Etienne Robillard <smart@open-neurosecurity.org>
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program. If not, see <https://www.gnu.org/licenses/>.
 **/
const likeBtn = document.getElementById('likeBtn');
let count = document.getElementById("likes-count");
let counter = document.getElementById('count');

async function addVoteToPost(url) {
      let response = await fetch(url, {
		  method: "POST", 
		  credentials: "same-origin"})
		.then((response) => response.json())
		.then((data) => {
			//console.log(data);
			if (data.code == 200) {
				likeBtn.classList.add('active');
				likeBtn.disabled = true;
				counter.textContent = parseInt(data.count) + 1;
			} else {
				likeBtn.classList.add('aquablue');
				count.classList.add('aquablue');
				count.innerHTML = "";
				count.textContent = 'Off-limit!';
			}
		});
}

likeBtn.addEventListener('click', (event) => {
    const articleid = event.currentTarget.getAttribute('data-article-id');
	const url = '/webhooks.php?id=' + articleid;
	addVoteToPost(url);
});
