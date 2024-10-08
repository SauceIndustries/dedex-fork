<?php

namespace DedexBundle\Entity\Ern43;

/**
 * Class representing MessageHeaderType
 *
 * A Composite placed at the beginning of each DdexMessage providing information about the Message, such as MessageSender, MessageRecipient and a Message creation time stamp.
 * XSD Type: MessageHeader
 */
class MessageHeaderType
{
    /**
     * A xs:string used to uniquely identify the thread of Messages of which the current Message is a part. The MessageThreadId shall be, in combination with the DdexPartyId (DPID) of the MessageSender, globally unique. Thus, a MessageSender shall never re-use a MessageThreadId. One example of such a 'thread' is the chain of NewReleaseMessages being sent from ReleaseCreator to wholesale ReleaseDistributor 1 to retail DSP when communicating information about the same Release(s). A common MessageThreadId will allow all these messages to be tied together.
     *
     * @var string $messageThreadId
     */
    private $messageThreadId = null;

    /**
     * A xs:string used to uniquely identify the current Message. The MessageId shall be, in combination with the DdexPartyId (DPID) of the MessageSender, globally unique. Thus, a MessageSender shall never re-use a MessageId.
     *
     * @var string $messageId
     */
    private $messageId = null;

    /**
     * The FileName, possibly including the FilePath, of the XML File containing the current Message.
     *
     * @var string $messageFileName
     */
    private $messageFileName = null;

    /**
     * A Composite containing details of the MessageSender.
     *
     * @var \DedexBundle\Entity\Ern43\MessagingPartyWithoutCodeType $messageSender
     */
    private $messageSender = null;

    /**
     * A Composite containing details of the Party on whose behalf the Message is sent.
     *
     * @var \DedexBundle\Entity\Ern43\MessagingPartyWithoutCodeType $sentOnBehalfOf
     */
    private $sentOnBehalfOf = null;

    /**
     * A Composite containing details of the MessageRecipient. Note that if multiple MessageRecipients are provided, all of them will be able to read this information.
     *
     * @var \DedexBundle\Entity\Ern43\MessagingPartyWithoutCodeType[] $messageRecipient
     */
    private $messageRecipient = [
        
    ];

    /**
     * The DateTime on which the Message was created (the only allowed format is ISO 8601: YYYY-MM-DDThh:mm:ssTZD).
     *
     * @var \DateTime $messageCreatedDateTime
     */
    private $messageCreatedDateTime = null;

    /**
     * A Composite containing information about Parties in between the original MessageSender and ultimate MessageRecipient.
     *
     * @var \DedexBundle\Entity\Ern43\MessageAuditTrailEventType[] $messageAuditTrail
     */
    private $messageAuditTrail = null;

    /**
     * The indicator used to distinguish a live Message from a test Message.
     *
     * @var string $messageControlType
     */
    private $messageControlType = null;

    /**
     * Gets as messageThreadId
     *
     * A xs:string used to uniquely identify the thread of Messages of which the current Message is a part. The MessageThreadId shall be, in combination with the DdexPartyId (DPID) of the MessageSender, globally unique. Thus, a MessageSender shall never re-use a MessageThreadId. One example of such a 'thread' is the chain of NewReleaseMessages being sent from ReleaseCreator to wholesale ReleaseDistributor 1 to retail DSP when communicating information about the same Release(s). A common MessageThreadId will allow all these messages to be tied together.
     *
     * @return string
     */
    public function getMessageThreadId()
    {
        return $this->messageThreadId;
    }

    /**
     * Sets a new messageThreadId
     *
     * A xs:string used to uniquely identify the thread of Messages of which the current Message is a part. The MessageThreadId shall be, in combination with the DdexPartyId (DPID) of the MessageSender, globally unique. Thus, a MessageSender shall never re-use a MessageThreadId. One example of such a 'thread' is the chain of NewReleaseMessages being sent from ReleaseCreator to wholesale ReleaseDistributor 1 to retail DSP when communicating information about the same Release(s). A common MessageThreadId will allow all these messages to be tied together.
     *
     * @param string $messageThreadId
     * @return self
     */
    public function setMessageThreadId($messageThreadId)
    {
        $this->messageThreadId = $messageThreadId;
        return $this;
    }

    /**
     * Gets as messageId
     *
     * A xs:string used to uniquely identify the current Message. The MessageId shall be, in combination with the DdexPartyId (DPID) of the MessageSender, globally unique. Thus, a MessageSender shall never re-use a MessageId.
     *
     * @return string
     */
    public function getMessageId()
    {
        return $this->messageId;
    }

    /**
     * Sets a new messageId
     *
     * A xs:string used to uniquely identify the current Message. The MessageId shall be, in combination with the DdexPartyId (DPID) of the MessageSender, globally unique. Thus, a MessageSender shall never re-use a MessageId.
     *
     * @param string $messageId
     * @return self
     */
    public function setMessageId($messageId)
    {
        $this->messageId = $messageId;
        return $this;
    }

    /**
     * Gets as messageFileName
     *
     * The FileName, possibly including the FilePath, of the XML File containing the current Message.
     *
     * @return string
     */
    public function getMessageFileName()
    {
        return $this->messageFileName;
    }

    /**
     * Sets a new messageFileName
     *
     * The FileName, possibly including the FilePath, of the XML File containing the current Message.
     *
     * @param string $messageFileName
     * @return self
     */
    public function setMessageFileName($messageFileName)
    {
        $this->messageFileName = $messageFileName;
        return $this;
    }

    /**
     * Gets as messageSender
     *
     * A Composite containing details of the MessageSender.
     *
     * @return \DedexBundle\Entity\Ern43\MessagingPartyWithoutCodeType
     */
    public function getMessageSender()
    {
        return $this->messageSender;
    }

    /**
     * Sets a new messageSender
     *
     * A Composite containing details of the MessageSender.
     *
     * @param \DedexBundle\Entity\Ern43\MessagingPartyWithoutCodeType $messageSender
     * @return self
     */
    public function setMessageSender(\DedexBundle\Entity\Ern43\MessagingPartyWithoutCodeType $messageSender)
    {
        $this->messageSender = $messageSender;
        return $this;
    }

    /**
     * Gets as sentOnBehalfOf
     *
     * A Composite containing details of the Party on whose behalf the Message is sent.
     *
     * @return \DedexBundle\Entity\Ern43\MessagingPartyWithoutCodeType
     */
    public function getSentOnBehalfOf()
    {
        return $this->sentOnBehalfOf;
    }

    /**
     * Sets a new sentOnBehalfOf
     *
     * A Composite containing details of the Party on whose behalf the Message is sent.
     *
     * @param \DedexBundle\Entity\Ern43\MessagingPartyWithoutCodeType $sentOnBehalfOf
     * @return self
     */
    public function setSentOnBehalfOf(?\DedexBundle\Entity\Ern43\MessagingPartyWithoutCodeType $sentOnBehalfOf = null)
    {
        $this->sentOnBehalfOf = $sentOnBehalfOf;
        return $this;
    }

    /**
     * Adds as messageRecipient
     *
     * A Composite containing details of the MessageRecipient. Note that if multiple MessageRecipients are provided, all of them will be able to read this information.
     *
     * @return self
     * @param \DedexBundle\Entity\Ern43\MessagingPartyWithoutCodeType $messageRecipient
     */
    public function addToMessageRecipient(\DedexBundle\Entity\Ern43\MessagingPartyWithoutCodeType $messageRecipient)
    {
        $this->messageRecipient[] = $messageRecipient;
        return $this;
    }

    /**
     * isset messageRecipient
     *
     * A Composite containing details of the MessageRecipient. Note that if multiple MessageRecipients are provided, all of them will be able to read this information.
     *
     * @param int|string $index
     * @return bool
     */
    public function issetMessageRecipient($index)
    {
        return isset($this->messageRecipient[$index]);
    }

    /**
     * unset messageRecipient
     *
     * A Composite containing details of the MessageRecipient. Note that if multiple MessageRecipients are provided, all of them will be able to read this information.
     *
     * @param int|string $index
     * @return void
     */
    public function unsetMessageRecipient($index)
    {
        unset($this->messageRecipient[$index]);
    }

    /**
     * Gets as messageRecipient
     *
     * A Composite containing details of the MessageRecipient. Note that if multiple MessageRecipients are provided, all of them will be able to read this information.
     *
     * @return \DedexBundle\Entity\Ern43\MessagingPartyWithoutCodeType[]
     */
    public function getMessageRecipient()
    {
        return $this->messageRecipient;
    }

    /**
     * Sets a new messageRecipient
     *
     * A Composite containing details of the MessageRecipient. Note that if multiple MessageRecipients are provided, all of them will be able to read this information.
     *
     * @param \DedexBundle\Entity\Ern43\MessagingPartyWithoutCodeType[] $messageRecipient
     * @return self
     */
    public function setMessageRecipient(array $messageRecipient)
    {
        $this->messageRecipient = $messageRecipient;
        return $this;
    }

    /**
     * Gets as messageCreatedDateTime
     *
     * The DateTime on which the Message was created (the only allowed format is ISO 8601: YYYY-MM-DDThh:mm:ssTZD).
     *
     * @return \DateTime
     */
    public function getMessageCreatedDateTime()
    {
        return $this->messageCreatedDateTime;
    }

    /**
     * Sets a new messageCreatedDateTime
     *
     * The DateTime on which the Message was created (the only allowed format is ISO 8601: YYYY-MM-DDThh:mm:ssTZD).
     *
     * @param \DateTime $messageCreatedDateTime
     * @return self
     */
    public function setMessageCreatedDateTime(\DateTime $messageCreatedDateTime)
    {
        $this->messageCreatedDateTime = $messageCreatedDateTime;
        return $this;
    }

    /**
     * Adds as messageAuditTrailEvent
     *
     * A Composite containing information about Parties in between the original MessageSender and ultimate MessageRecipient.
     *
     * @return self
     * @param \DedexBundle\Entity\Ern43\MessageAuditTrailEventType $messageAuditTrailEvent
     */
    public function addToMessageAuditTrail(\DedexBundle\Entity\Ern43\MessageAuditTrailEventType $messageAuditTrailEvent)
    {
        $this->messageAuditTrail[] = $messageAuditTrailEvent;
        return $this;
    }

    /**
     * isset messageAuditTrail
     *
     * A Composite containing information about Parties in between the original MessageSender and ultimate MessageRecipient.
     *
     * @param int|string $index
     * @return bool
     */
    public function issetMessageAuditTrail($index)
    {
        return isset($this->messageAuditTrail[$index]);
    }

    /**
     * unset messageAuditTrail
     *
     * A Composite containing information about Parties in between the original MessageSender and ultimate MessageRecipient.
     *
     * @param int|string $index
     * @return void
     */
    public function unsetMessageAuditTrail($index)
    {
        unset($this->messageAuditTrail[$index]);
    }

    /**
     * Gets as messageAuditTrail
     *
     * A Composite containing information about Parties in between the original MessageSender and ultimate MessageRecipient.
     *
     * @return \DedexBundle\Entity\Ern43\MessageAuditTrailEventType[]
     */
    public function getMessageAuditTrail()
    {
        return $this->messageAuditTrail;
    }

    /**
     * Sets a new messageAuditTrail
     *
     * A Composite containing information about Parties in between the original MessageSender and ultimate MessageRecipient.
     *
     * @param \DedexBundle\Entity\Ern43\MessageAuditTrailEventType[] $messageAuditTrail
     * @return self
     */
    public function setMessageAuditTrail(array $messageAuditTrail = null)
    {
        $this->messageAuditTrail = $messageAuditTrail;
        return $this;
    }

    /**
     * Gets as messageControlType
     *
     * The indicator used to distinguish a live Message from a test Message.
     *
     * @return string
     */
    public function getMessageControlType()
    {
        return $this->messageControlType;
    }

    /**
     * Sets a new messageControlType
     *
     * The indicator used to distinguish a live Message from a test Message.
     *
     * @param string $messageControlType
     * @return self
     */
    public function setMessageControlType($messageControlType)
    {
        $this->messageControlType = $messageControlType;
        return $this;
    }
}

