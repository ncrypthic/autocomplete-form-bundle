<?php 

namespace LLA\AutocompleteFormBundle\Tests\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="example")
 */
class ExampleEntity
{
    /**
     *
     * @var int
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;
    /**
     *
     * @var string
     * @ORM\Column(name="address", type="string", nullable=false)
     */
    private $address;

    public function getId()
    {
        return $this->id;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function setAddress($address)
    {
        $this->address = $address;
        return $this;
    }
}
