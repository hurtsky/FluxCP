<?php
/**
 * Basically acts as an uppermost container holding the LoginServer and Athena
 * instances on a top level.
 */
class Flux_LoginAthenaGroup
{
    /**
     * Global server name, representing all Athena servers.
     *
     * @var string
     */
    public $serverName;

    /**
     * Connection to the MySQL server.
     *
     * @var Flux_Connection
     */
    public $connection;

    /**
     * Main login server for the contained Athena servers.
     *
     * @var Flux_LoginServer
     */
    public $loginServer;

    /**
     * Database used for the login-related SQL operations.
     *
     * @var string
     */
    public $loginDatabase;

    /**
     * Logs database.
     *
     * @var string
     */
    public $logsDatabase;

    /**
     * Array of Flux_Athena instances.
     *
     * @var array
     */
    public $athenaServers = [];

    /**
     * Construct new Flux_LoginAthenaGroup instance.
     */
    public function __construct($serverName, Flux_Connection $connection, Flux_LoginServer $loginServer, array $athenaServers = [])
    {
        $this->serverName = $serverName;
        $this->connection = $connection;
        $this->loginServer = $loginServer;
        $this->loginDatabase = $loginServer->config->getDatabase();
        $this->logsDatabase = $connection->logsDbConfig->getDatabase();

        // Assign connection to LoginServer, used mainly to enable
        // authentication feature.
        $this->loginServer->setConnection($connection);

        foreach ($athenaServers as $athenaServer) {
            $this->addAthenaServer($athenaServer);
        }
    }

    /**
     * Add an Athena instance to the current collection.
     *
     * @return mixed Returns false if login servers aren't identical.
     */
    public function addAthenaServer(Flux_Athena $athenaServer)
    {
        if ($athenaServer->loginServer === $this->loginServer) {
            $athenaServer->setLoginAthenaGroup($this);
            $athenaServer->setConnection($this->connection);
            $this->athenaServers[] = $athenaServer;

            return $this->athenaServers;
        } else {
            return false;
        }
    }

    /**
     * See Flux_LoginServer->isAuth().
     *
     * @param string $username
     * @param string $password
     *
     * @return bool
     */
    public function isAuth($username, $password)
    {
        return $this->loginServer->isAuth($username, $password);
    }
}
