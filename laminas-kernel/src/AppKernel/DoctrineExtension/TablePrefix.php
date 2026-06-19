<?php

/*
 * Tchua biet su dung nhu the nao, muc dich la loai bo table-prefix ra khoi filename cua entity Users.php thay vi ViwebUsers.php
 */

namespace AppKernel\DoctrineExtension;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;

class TablePrefix {

    protected $prefix = 'viweb_';

    public function __construct($prefix) {
        $this->prefix = (string) $prefix;
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs) {
        $classMetadata = $eventArgs->getClassMetadata();

        if (!$classMetadata->isInheritanceTypeSingleTable() || $classMetadata->getName() === $classMetadata->rootEntityName) {
            $classMetadata->setTableName($this->prefix . $classMetadata->getTableName());
        }

        foreach ($classMetadata->getAssociationMappings() as $fieldName => $mapping) {
            if ($mapping['type'] == \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_MANY && $mapping['isOwningSide']) {
                $mappedTableName = $mapping['joinTable']['name'];
                $classMetadata->associationMappings[$fieldName]['joinTable']['name'] = $this->prefix . $mappedTableName;
            }
        }
    }

}
