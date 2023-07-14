<?php
/**
 * MageINIC
 * Copyright (C) 2023 MageINIC <support@mageinic.com>
 *
 * NOTICE OF LICENSE
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see https://opensource.org/licenses/gpl-3.0.html.
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category MageINIC
 * @package MageINIC_NewsletterPopupGraphQl
 * @copyright Copyright (c) 2023 MageINIC (https://www.mageinic.com/)
 * @license https://opensource.org/licenses/gpl-3.0.html GNU General Public License,version 3 (GPL-3.0)
 * @author MageINIC <support@mageinic.com>
 */

namespace MageINIC\NewsletterPopupGraphQl\Model\Resolver;

use Exception;
use MageINIC\NewsletterPopup\Block\NewsletterPopup;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfig;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface as StoreManager;
use Psr\Log\LoggerInterface as Logger;

/**
 * @inheritdoc
 */
class OfferImage implements ResolverInterface
{
    /**
     * @var StoreManager
     */
    protected StoreManager $storeManager;

    /**
     * @var ScopeConfig
     */
    protected ScopeConfig $scopeConfig;

    /**
     * @var Repository
     */
    private Repository $assetRepository;

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * @var File
     */
    private File $fileSystemIo;

    /**
     * OfferImage Constructor.
     *
     * @param Repository $assetRepository
     * @param StoreManager $storeManager
     * @param ScopeConfig $scopeConfig
     * @param File $fileSystemIo
     * @param Logger $logger
     */
    public function __construct(
        Repository   $assetRepository,
        StoreManager $storeManager,
        ScopeConfig  $scopeConfig,
        File         $fileSystemIo,
        Logger       $logger
    ) {
        $this->assetRepository = $assetRepository;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->fileSystemIo = $fileSystemIo;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field       $field,
        $context,
        ResolveInfo $info,
        array       $value = null,
        array       $args = null
    ) {
        $image = '';
        $imagePath = '';
        $fileId = NewsletterPopup::DEFAULT_IMAGE_PATH;
        $params = ['area' => Area::AREA_FRONTEND];

        try {
            $asset = $this->assetRepository->getUrlWithParams($fileId, $params);
            $fileInfo = $this->fileSystemIo->getPathInfo($fileId);
            $image = empty($this->getOfferImage()) ? $fileInfo['basename'] : $this->getOfferImage();
            $imagePath = empty($this->getOfferImage()) ? $asset : $this->getOfferImageUrl();
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
        }

        return [
            'name' => $image,
            'format' => substr($image, strpos($image, ".") + 1),
            'full_path' => $imagePath
        ];
    }

    /**
     * Offer Image path
     *
     * @return string|null
     */
    protected function getOfferImage(): ?string
    {
        return $this->scopeConfig->getValue(
            NewsletterPopup::XML_PATH_OFFER_IMAGE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Offer Image Url
     *
     * @return string
     * @throws NoSuchEntityException
     */
    protected function getOfferImageUrl(): string
    {
        return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA)
            . NewsletterPopup::MEDIA_PATH . $this->getOfferImage();
    }
}
