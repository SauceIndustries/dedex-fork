<?php

namespace DedexBundle\Entity\Ern43;

/**
 * Class representing PeriodWithoutFlagsType
 *
 * A Composite containing details about a Period of Time. Periods are typically describedby at least a StartDate or EndDate (or StartDateTime or EndDateTime) where the StartDate(Time) and EndDate(Time) are included in the Period. Thus a one-day period can be described by using the same date in the StartDate and EndDate. If two subsequent Periods form a continuum (i.e. with no break in-between) there are two ways to express this: (a) if using dates, the EndDate of the first Period must be one day before the StartDate of the second Period; (b) if using date times, the EndDateTime of the first Period must be the same as the StartDateTime of the second Period.
 * Explanatory Note: This Composite is named PeriodWithoutFlags to disambiguate it from the basic Period Composite.
 * XSD Type: PeriodWithoutFlags
 */
class PeriodWithoutFlagsType
{
    /**
     * A Composite containing details of the Date and Place of an Event that marks the beginning of the Period (in ISO 8601 format: YYYY-MM-DD). The StartDate must be no later than the EndDate if both are provided. StartDate and EndDate are deprecated and StartDateTime/EndDateTime should be used instead. DDEX advises that StartDate and EndDate may be removed at a future date and therefore recommends against using them.
     *
     * @var \DedexBundle\Entity\Ern43\EventDateWithCurrentTerritoryType $startDate
     */
    private $startDate = null;

    /**
     * A Composite containing details of the Date and Place of an Event that marks the end of the Period (in ISO 8601 format: YYYY-MM-DD). The EndDate must not be earlier than the StartDate if both are provided. StartDate and EndDate are deprecated and StartDateTime/EndDateTime should be used instead. DDEX advises that StartDate and EndDate may be removed at a future date and therefore recommends against using them.
     *
     * @var \DedexBundle\Entity\Ern43\EventDateWithCurrentTerritoryType $endDate
     */
    private $endDate = null;

    /**
     * A Composite containing details of the DateTime and Place of an Event that marks the beginning of the Period (in ISO 8601 format: YYYY-MM-DDThh:mm:ss). The StartDateTime must be no later than the EndDateTime if both are provided.
     *
     * @var \DedexBundle\Entity\Ern43\EventDateTimeWithoutFlagsType $startDateTime
     */
    private $startDateTime = null;

    /**
     * A Composite containing details of the DateTime and Place of an Event that marks the end of the Period (in ISO 8601 format: YYYY-MM-DDThh:mm:ss). The EndDateTime must not be earlier than the StartDateTime if both are provided.
     *
     * @var \DedexBundle\Entity\Ern43\EventDateTimeWithoutFlagsType $endDateTime
     */
    private $endDateTime = null;

    /**
     * Gets as startDate
     *
     * A Composite containing details of the Date and Place of an Event that marks the beginning of the Period (in ISO 8601 format: YYYY-MM-DD). The StartDate must be no later than the EndDate if both are provided. StartDate and EndDate are deprecated and StartDateTime/EndDateTime should be used instead. DDEX advises that StartDate and EndDate may be removed at a future date and therefore recommends against using them.
     *
     * @return \DedexBundle\Entity\Ern43\EventDateWithCurrentTerritoryType
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Sets a new startDate
     *
     * A Composite containing details of the Date and Place of an Event that marks the beginning of the Period (in ISO 8601 format: YYYY-MM-DD). The StartDate must be no later than the EndDate if both are provided. StartDate and EndDate are deprecated and StartDateTime/EndDateTime should be used instead. DDEX advises that StartDate and EndDate may be removed at a future date and therefore recommends against using them.
     *
     * @param \DedexBundle\Entity\Ern43\EventDateWithCurrentTerritoryType $startDate
     * @return self
     */
    public function setStartDate(?\DedexBundle\Entity\Ern43\EventDateWithCurrentTerritoryType $startDate = null)
    {
        $this->startDate = $startDate;
        return $this;
    }

    /**
     * Gets as endDate
     *
     * A Composite containing details of the Date and Place of an Event that marks the end of the Period (in ISO 8601 format: YYYY-MM-DD). The EndDate must not be earlier than the StartDate if both are provided. StartDate and EndDate are deprecated and StartDateTime/EndDateTime should be used instead. DDEX advises that StartDate and EndDate may be removed at a future date and therefore recommends against using them.
     *
     * @return \DedexBundle\Entity\Ern43\EventDateWithCurrentTerritoryType
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * Sets a new endDate
     *
     * A Composite containing details of the Date and Place of an Event that marks the end of the Period (in ISO 8601 format: YYYY-MM-DD). The EndDate must not be earlier than the StartDate if both are provided. StartDate and EndDate are deprecated and StartDateTime/EndDateTime should be used instead. DDEX advises that StartDate and EndDate may be removed at a future date and therefore recommends against using them.
     *
     * @param \DedexBundle\Entity\Ern43\EventDateWithCurrentTerritoryType $endDate
     * @return self
     */
    public function setEndDate(?\DedexBundle\Entity\Ern43\EventDateWithCurrentTerritoryType $endDate = null)
    {
        $this->endDate = $endDate;
        return $this;
    }

    /**
     * Gets as startDateTime
     *
     * A Composite containing details of the DateTime and Place of an Event that marks the beginning of the Period (in ISO 8601 format: YYYY-MM-DDThh:mm:ss). The StartDateTime must be no later than the EndDateTime if both are provided.
     *
     * @return \DedexBundle\Entity\Ern43\EventDateTimeWithoutFlagsType
     */
    public function getStartDateTime()
    {
        return $this->startDateTime;
    }

    /**
     * Sets a new startDateTime
     *
     * A Composite containing details of the DateTime and Place of an Event that marks the beginning of the Period (in ISO 8601 format: YYYY-MM-DDThh:mm:ss). The StartDateTime must be no later than the EndDateTime if both are provided.
     *
     * @param \DedexBundle\Entity\Ern43\EventDateTimeWithoutFlagsType $startDateTime
     * @return self
     */
    public function setStartDateTime(?\DedexBundle\Entity\Ern43\EventDateTimeWithoutFlagsType $startDateTime = null)
    {
        $this->startDateTime = $startDateTime;
        return $this;
    }

    /**
     * Gets as endDateTime
     *
     * A Composite containing details of the DateTime and Place of an Event that marks the end of the Period (in ISO 8601 format: YYYY-MM-DDThh:mm:ss). The EndDateTime must not be earlier than the StartDateTime if both are provided.
     *
     * @return \DedexBundle\Entity\Ern43\EventDateTimeWithoutFlagsType
     */
    public function getEndDateTime()
    {
        return $this->endDateTime;
    }

    /**
     * Sets a new endDateTime
     *
     * A Composite containing details of the DateTime and Place of an Event that marks the end of the Period (in ISO 8601 format: YYYY-MM-DDThh:mm:ss). The EndDateTime must not be earlier than the StartDateTime if both are provided.
     *
     * @param \DedexBundle\Entity\Ern43\EventDateTimeWithoutFlagsType $endDateTime
     * @return self
     */
    public function setEndDateTime(?\DedexBundle\Entity\Ern43\EventDateTimeWithoutFlagsType $endDateTime = null)
    {
        $this->endDateTime = $endDateTime;
        return $this;
    }
}

