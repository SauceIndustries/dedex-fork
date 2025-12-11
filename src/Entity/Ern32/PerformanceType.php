<?php

namespace DedexBundle\Entity\Ern32;

/**
 * Class representing PerformanceType
 *
 * A Composite containing details of a
 *  Performance.
 * XSD Type: Performance
 */
class PerformanceType
{

    /**
     * The territory of the
     *  Performance.
     *
     * @var \DedexBundle\Entity\Ern32\AllTerritoryCodeType $territory
     */
    private $territory = null;

    /**
     * A Composite containing details of the Date
     *  of the Performance. This is a string with the syntax YYYY[-MM[-DD]]. The Place of
     *  the Performance should not be specified in the TerritoryCode and
     *  LocationDescription attributes. Instead, the sibling Territory element should be
     *  used.
     *
     * @var \DedexBundle\Entity\Ern32\EventDateType $date
     */
    private $date = null;

    /**
     * Gets as territory
     *
     * The territory of the
     *  Performance.
     *
     * @return \DedexBundle\Entity\Ern32\AllTerritoryCodeType
     */
    public function getTerritory()
    {
        return $this->territory;
    }

    /**
     * Sets a new territory
     *
     * The territory of the
     *  Performance.
     *
     * @param \DedexBundle\Entity\Ern32\AllTerritoryCodeType $territory
     * @return self
     */
    public function setTerritory(\DedexBundle\Entity\Ern32\AllTerritoryCodeType $territory)
    {
        $this->territory = $territory;
        return $this;
    }

    /**
     * Gets as date
     *
     * A Composite containing details of the Date
     *  of the Performance. This is a string with the syntax YYYY[-MM[-DD]]. The Place of
     *  the Performance should not be specified in the TerritoryCode and
     *  LocationDescription attributes. Instead, the sibling Territory element should be
     *  used.
     *
     * @return \DedexBundle\Entity\Ern32\EventDateType
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Sets a new date
     *
     * A Composite containing details of the Date
     *  of the Performance. This is a string with the syntax YYYY[-MM[-DD]]. The Place of
     *  the Performance should not be specified in the TerritoryCode and
     *  LocationDescription attributes. Instead, the sibling Territory element should be
     *  used.
     *
     * @param \DedexBundle\Entity\Ern32\EventDateType $date
     * @return self
     */
    public function setDate(\DedexBundle\Entity\Ern32\EventDateType $date)
    {
        $this->date = $date;
        return $this;
    }


}

