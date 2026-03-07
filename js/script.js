function formatNama(nama) {
  if (!nama) return "";
  return nama
    .toLowerCase()
    .split(" ")
    .map(k => k.charAt(0).toUpperCase() + k.slice(1))
    .join(" ");
}