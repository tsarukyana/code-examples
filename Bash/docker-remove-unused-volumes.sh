#! /usr/bin/bash

for container in $(docker ps -q); do
  echo "Container ID: $container"
  docker inspect $container | grep -i mountpoint
done

# Get a list of all volumes
all_volumes=$(docker volume ls -q)

# Get a list of volumes currently in use
used_volumes=$(docker ps -q | xargs docker inspect --format '{{ range .Mounts }}{{ .Name }} {{ end }}')

# Find unused volumes
unused_volumes=$(echo $all_volumes | tr ' ' '\n' | grep -v -F -x -f <(echo $used_volumes | tr ' ' '\n'))

# Print and optionally delete unused volumes
echo "Unused volumes:"
echo "$unused_volumes"

# Uncomment the following line to delete unused volumes
# echo "$unused_volumes" | xargs -I {} docker volume rm {}


