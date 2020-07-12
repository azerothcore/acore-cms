module.exports = {
    /**@type {string} name of the main service to run*/
    docker_service_name: process.env.DOCKER_SERVICE_NAME || "node-server",
    /**@type {string} enable some checks for db*/
    docker_has_db: process.env.DOCKER_HAS_DB === "true" || false,
    /**@type {string} name of the db service*/
    docker_db_service_name: process.env.DOCKER_DB_SERVICE_NAME || "db",
    /**@type {string} inject ssh key in build arguments*/
    docker_inject_sshkey: process.env.DOCKER_INJECT_SSHKEY === "true" || false,
    /**@type {string} profix for command to run*/
    docker_run_cmd: process.env.DOCKER_RUN_CMD || 'npm run',
    /**@type {string} overriding extra arguments*/
    docker_extra_args: process.env.DOCKER_EXTRA_ARGS || "",
    /**@type {string} directory of docker files */
    docker_base_dir: process.env.DOCKER_BASE_DIR || undefined,
    /**@type {string} specify jsdocker-compose path */
    docker_jsdc_dir: process.env.DOCKER_JSDC_DIR || "/apps/jsdocker-compose",
    /**@type {string} enable/disable suppoort for install-changed script*/
    docker_packagehash_support: process.env.DOCKER_EXTRA_FILE === "true" || false,
}